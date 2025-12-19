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
use Tourze\GaNetBundle\Entity\CommissionRule;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Repository\CampaignRepository;
use Tourze\GaNetBundle\Repository\CommissionRuleRepository;
use Tourze\GaNetBundle\Service\GaNetApiClient;

#[AsCommand(
    name: 'ganet:sync-commissions',
    description: '同步成果网佣金规则数据',
)]
final class SyncCommissionsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GaNetApiClient $apiClient,
        private readonly CampaignRepository $campaignRepository,
        private readonly CommissionRuleRepository $commissionRuleRepository,
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

        $io->title('同步成果网佣金规则');
        $io->section(sprintf('Publisher ID: %d, Website ID: %d', $publisherId, $websiteId));

        try {
            $this->syncCommissions($io, $publisher, $websiteId);
            $io->success('佣金规则同步完成');

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

    private function syncCommissions(SymfonyStyle $io, Publisher $publisher, int $websiteId): void
    {
        $campaigns = $this->getCampaignsForSync($io, $publisher);
        if (null === $campaigns) {
            return;
        }

        $totalSynced = 0;

        foreach ($campaigns as $campaign) {
            $syncedCount = $this->syncCampaignCommissions($io, $publisher, $websiteId, $campaign);
            $totalSynced += $syncedCount;
        }

        $io->success(sprintf('同步了 %d 个佣金规则', $totalSynced));
    }

    /**
     * @return array<Campaign>|null
     */
    private function getCampaignsForSync(SymfonyStyle $io, Publisher $publisher): ?array
    {
        $campaigns = $this->campaignRepository->findBy(['publisher' => $publisher]);

        if (0 === count($campaigns)) {
            $io->warning('没有找到活动数据，请先同步活动列表');

            return null;
        }

        return $campaigns;
    }

    private function syncCampaignCommissions(SymfonyStyle $io, Publisher $publisher, int $websiteId, Campaign $campaign): int
    {
        try {
            $campaignId = $campaign->getId();
            if (null === $campaignId) {
                $io->warning(sprintf('跳过没有ID的活动: %s', $campaign->getName()));

                return 0;
            }

            $commissions = $this->fetchCampaignCommissions($io, $publisher, $websiteId, $campaignId);
            if (null === $commissions) {
                return 0;
            }

            $syncedCount = $this->processCommissions($campaign, $commissions);
            $this->entityManager->flush();

            return $syncedCount;
        } catch (\Exception $e) {
            $campaignId = $campaign->getId();
            $campaignIdDisplay = $campaignId ?? 'null';
            $io->warning(sprintf('活动 %s 的佣金规则同步失败: %s', $campaignIdDisplay, $e->getMessage()));

            return 0;
        }
    }

    /**
     * @return array<array<string, mixed>>|null
     */
    private function fetchCampaignCommissions(SymfonyStyle $io, Publisher $publisher, int $websiteId, int $campaignId): ?array
    {
        $data = $this->apiClient->getCommissionList($publisher, $websiteId, $campaignId);
        $commissions = $data['commissions'] ?? [];

        if (!is_array($commissions)) {
            $io->warning(sprintf('活动 %d 的佣金规则数据格式错误', $campaignId));

            return null;
        }

        // 确保数组元素都是正确的格式
        $validCommissions = [];
        foreach ($commissions as $commission) {
            if (is_array($commission)) {
                /** @var array<string, mixed> $commission */
                $validCommissions[] = $commission;
            }
        }

        return $validCommissions;
    }

    /**
     * @param array<array<string, mixed>> $commissions
     */
    private function processCommissions(Campaign $campaign, array $commissions): int
    {
        $syncedCount = 0;

        foreach ($commissions as $commissionData) {
            if ($this->processCommissionItem($campaign, $commissionData)) {
                ++$syncedCount;
            }
        }

        return $syncedCount;
    }

    /**
     * @param array<string, mixed> $commissionData
     */
    private function processCommissionItem(Campaign $campaign, array $commissionData): bool
    {
        $commissionId = $this->extractCommissionId($commissionData);
        if (null === $commissionId) {
            return false;
        }

        $commission = $this->getOrCreateCommission($campaign, $commissionId);
        $commission->updateFromApiData($commissionData);

        return true;
    }

    /**
     * @param array<string, mixed> $commissionData
     */
    private function extractCommissionId(array $commissionData): ?int
    {
        $commissionId = $commissionData['id'] ?? null;
        if (!is_int($commissionId) && !is_numeric($commissionId)) {
            return null;
        }

        return (int) $commissionId;
    }

    private function getOrCreateCommission(Campaign $campaign, int $commissionId): CommissionRule
    {
        $commission = $this->commissionRuleRepository
            ->findOneBy(['id' => $commissionId, 'campaign' => $campaign])
        ;

        if (null === $commission) {
            $commission = new CommissionRule();
            $commission->setCampaign($campaign);
            $commission->setId($commissionId);
            $this->entityManager->persist($commission);
        }

        return $commission;
    }
}
