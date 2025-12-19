<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\PromotionCampaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Repository\CampaignRepository;
use Tourze\GaNetBundle\Service\GaNetApiClient;

#[AsCommand(
    name: 'ganet:sync-promotions',
    description: '同步成果网促销活动数据',
)]
final class SyncPromotionsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GaNetApiClient $apiClient,
        private readonly CampaignRepository $campaignRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 从环境变量获取配置
        $publisherIdRaw = $_ENV['GA_NET_PUBLISHER_ID'] ?? '0';
        $websiteIdRaw = $_ENV['GA_NET_WEBSITE_ID'] ?? '0';
        $tokenRaw = $_ENV['GA_NET_TOKEN'] ?? '';

        if (!is_string($publisherIdRaw) || !is_string($websiteIdRaw) || !is_string($tokenRaw)) {
            $io->error('环境变量配置错误：必须为字符串类型');

            return Command::FAILURE;
        }

        $publisherId = (int) $publisherIdRaw;
        $websiteId = (int) $websiteIdRaw;
        $token = $tokenRaw;

        if (0 === $publisherId || 0 === $websiteId || '' === $token) {
            $io->error('必须在环境变量中设置 GA_NET_PUBLISHER_ID, GA_NET_WEBSITE_ID 和 GA_NET_TOKEN');

            return Command::FAILURE;
        }

        // 获取或创建Publisher
        $publisher = $this->getOrCreatePublisher($publisherId, $token);

        $io->title('同步成果网促销活动');
        $io->section(sprintf('Publisher ID: %d, Website ID: %d', $publisherId, $websiteId));

        try {
            $this->syncPromotions($io, $publisher, $websiteId);
            $io->success('促销活动同步完成');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error(sprintf('同步过程中发生错误: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }

    private function getOrCreatePublisher(int $publisherId, string $token): Publisher
    {
        $publisher = $this->entityManager->find(Publisher::class, $publisherId);

        if (null === $publisher) {
            $publisher = new Publisher();
            $publisher->setPublisherId($publisherId);
            $publisher->setToken($token);
            $this->entityManager->persist($publisher);
        } else {
            $publisher->setToken($token);
        }

        $this->entityManager->flush();

        return $publisher;
    }

    private function syncPromotions(SymfonyStyle $io, Publisher $publisher, int $websiteId): void
    {
        $campaigns = $this->getCampaignsForPromotion($io, $publisher);
        if (null === $campaigns) {
            return;
        }

        $totalSynced = 0;

        foreach ($campaigns as $campaign) {
            $syncedCount = $this->syncCampaignPromotions($io, $publisher, $websiteId, $campaign);
            $totalSynced += $syncedCount;
        }

        $io->success(sprintf('同步了 %d 个促销活动', $totalSynced));
    }

    /**
     * @return array<Campaign>|null
     */
    private function getCampaignsForPromotion(SymfonyStyle $io, Publisher $publisher): ?array
    {
        $campaigns = $this->campaignRepository->findBy(['publisher' => $publisher]);

        if (0 === count($campaigns)) {
            $io->warning('没有找到活动数据，请先同步活动列表');

            return null;
        }

        return $campaigns;
    }

    private function syncCampaignPromotions(SymfonyStyle $io, Publisher $publisher, int $websiteId, Campaign $campaign): int
    {
        try {
            $campaignId = $campaign->getId();
            if (null === $campaignId) {
                $io->warning(sprintf('跳过没有ID的活动: %s', $campaign->getName()));

                return 0;
            }

            $deals = $this->fetchCampaignDeals($io, $publisher, $websiteId, $campaignId);
            if (null === $deals) {
                return 0;
            }

            $syncedCount = $this->processDeals($publisher, $deals);
            $this->entityManager->flush();

            return $syncedCount;
        } catch (\Exception $e) {
            $campaignId = $campaign->getId();
            $campaignIdDisplay = $campaignId ?? 'null';
            $io->warning(sprintf('活动 %s 的促销数据同步失败: %s', $campaignIdDisplay, $e->getMessage()));

            return 0;
        }
    }

    /**
     * @return array<array<string, mixed>>|null
     */
    private function fetchCampaignDeals(SymfonyStyle $io, Publisher $publisher, int $websiteId, int $campaignId): ?array
    {
        $data = $this->apiClient->getCampaignDeals($publisher, $websiteId, $campaignId);
        $deals = $data['deals'] ?? [];

        if (!is_array($deals)) {
            $io->warning(sprintf('活动 %d 的促销数据格式错误', $campaignId));

            return null;
        }

        // 确保数组元素都是正确的格式
        $validDeals = [];
        foreach ($deals as $deal) {
            if (is_array($deal)) {
                /** @var array<string, mixed> $deal */
                $validDeals[] = $deal;
            }
        }

        return $validDeals;
    }

    /**
     * @param array<array<string, mixed>> $deals
     */
    private function processDeals(Publisher $publisher, array $deals): int
    {
        $syncedCount = 0;

        foreach ($deals as $dealData) {
            if ($this->processDealItem($publisher, $dealData)) {
                ++$syncedCount;
            }
        }

        return $syncedCount;
    }

    /**
     * @param array<string, mixed> $dealData
     */
    private function processDealItem(Publisher $publisher, array $dealData): bool
    {
        $dealId = $this->extractDealId($dealData);
        if (null === $dealId) {
            return false;
        }

        $promotion = $this->getOrCreatePromotion($publisher, $dealId);
        $promotion->updateFromApiData($dealData);

        return true;
    }

    /**
     * @param array<string, mixed> $dealData
     */
    private function extractDealId(array $dealData): ?int
    {
        $dealId = $dealData['id'] ?? null;
        if (!is_int($dealId) && !is_numeric($dealId)) {
            return null;
        }

        return (int) $dealId;
    }

    private function getOrCreatePromotion(Publisher $publisher, int $dealId): PromotionCampaign
    {
        $promotion = $this->entityManager->find(PromotionCampaign::class, $dealId);

        if (null === $promotion) {
            $promotion = new PromotionCampaign();
            $promotion->setPublisher($publisher);
            $promotion->setId($dealId);
            $this->entityManager->persist($promotion);
        }

        return $promotion;
    }
}
