<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\Settlement;
use Tourze\GaNetBundle\Service\GaNetApiClient;

#[AsCommand(
    name: 'ganet:sync-settlements',
    description: '同步成果网结算数据',
)]
class SyncSettlementsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GaNetApiClient $apiClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('settlement-month', null, InputOption::VALUE_REQUIRED, '结算月份 (YYYY-MM)', date('Y-m', strtotime('-1 month')))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 从环境变量获取配置
        $publisherIdRaw = $_ENV['GA_NET_PUBLISHER_ID'] ?? '0';
        $tokenRaw = $_ENV['GA_NET_TOKEN'] ?? '';

        if (!is_string($publisherIdRaw) || !is_string($tokenRaw)) {
            $io->error('环境变量配置错误：必须为字符串类型');

            return Command::FAILURE;
        }

        $publisherId = (int) $publisherIdRaw;
        $token = $tokenRaw;

        if (0 === $publisherId || '' === $token) {
            $io->error('必须在环境变量中设置 GA_NET_PUBLISHER_ID 和 GA_NET_TOKEN');

            return Command::FAILURE;
        }

        // 获取结算月份参数
        $settlementMonthRaw = $input->getOption('settlement-month');
        if (!is_string($settlementMonthRaw)) {
            $io->error('结算月份参数必须为字符串类型');

            return Command::FAILURE;
        }
        $settlementMonth = $settlementMonthRaw;

        // 获取或创建Publisher
        $publisher = $this->getOrCreatePublisher($publisherId, $token);

        $io->title('同步成果网结算数据');
        $io->section(sprintf('Publisher ID: %d, 结算月份: %s', $publisherId, $settlementMonth));

        try {
            $this->syncSettlements($io, $publisher, $settlementMonth);
            $io->success('结算数据同步完成');

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

    private function syncSettlements(SymfonyStyle $io, Publisher $publisher, string $month): void
    {
        $data = $this->apiClient->getTransactionBalanceReport($publisher, $month);
        $transactions = $data['transactions'] ?? [];
        $syncCount = 0;

        if (!is_array($transactions)) {
            throw new \RuntimeException('API返回的transactions字段不是数组类型');
        }

        foreach ($transactions as $transactionData) {
            if (!is_array($transactionData)) {
                continue; // 跳过无效数据
            }

            $transactionId = $transactionData['id'] ?? null;
            if (!is_int($transactionId) && !is_numeric($transactionId)) {
                continue; // 跳过没有有效ID的数据
            }

            $transactionId = (int) $transactionId;
            $settlement = $this->entityManager->find(Settlement::class, $transactionId);

            if (null === $settlement) {
                $settlement = new Settlement();
                $settlement->setPublisher($publisher);
                $settlement->setId($transactionId);
                $this->entityManager->persist($settlement);
            }

            /** @var array<string, mixed> $transactionData */
            $settlement->updateFromApiData($transactionData);
            ++$syncCount;
        }

        $this->entityManager->flush();
        $io->success(sprintf('同步了 %d 个结算记录', $syncCount));
    }
}
