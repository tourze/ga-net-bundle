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
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Service\GaNetApiClient;

#[AsCommand(
    name: 'ganet:sync-campaigns',
    description: '同步成果网活动列表数据',
)]
final class SyncCampaignsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GaNetApiClient $apiClient,
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

        $io->title('同步成果网活动列表');
        $io->section(sprintf('Publisher ID: %d, Website ID: %d', $publisherId, $websiteId));

        try {
            $this->syncCampaigns($io, $publisher, $websiteId);
            $io->success('活动列表同步完成');

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

    private function syncCampaigns(SymfonyStyle $io, Publisher $publisher, int $websiteId): void
    {
        $data = $this->apiClient->getCampaignList($publisher, $websiteId);

        $campaigns = $data['campaigns'] ?? [];
        $syncCount = 0;

        if (!is_array($campaigns)) {
            throw new \RuntimeException('API返回的campaigns字段不是数组类型');
        }

        foreach ($campaigns as $campaignData) {
            if (!is_array($campaignData)) {
                continue; // 跳过无效数据
            }

            $campaignId = $campaignData['id'] ?? null;
            if (!is_int($campaignId) && !is_numeric($campaignId)) {
                continue; // 跳过没有有效ID的数据
            }

            $campaignId = (int) $campaignId;
            $campaign = $this->entityManager->find(Campaign::class, $campaignId);

            if (null === $campaign) {
                $campaign = new Campaign();
                $campaign->setPublisher($publisher);
                $campaign->setId($campaignId);
                $this->entityManager->persist($campaign);
            }

            /** @var array<string, mixed> $campaignData */
            $campaign->updateFromApiData($campaignData);
            ++$syncCount;
        }

        $this->entityManager->flush();
        $io->success(sprintf('同步了 %d 个活动', $syncCount));
    }
}
