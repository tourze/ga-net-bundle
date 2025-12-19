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
use Tourze\GaNetBundle\Entity\Transaction;
use Tourze\GaNetBundle\Service\GaNetApiClient;

#[AsCommand(
    name: 'ganet:sync-transactions',
    description: '同步成果网交易数据',
)]
final class SyncTransactionsCommand extends Command
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
            ->addOption('start-date', null, InputOption::VALUE_REQUIRED, '开始日期 (YYYY-MM-DD)', date('Y-m-d', strtotime('-7 days')))
            ->addOption('end-date', null, InputOption::VALUE_REQUIRED, '结束日期 (YYYY-MM-DD)', date('Y-m-d'))
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

        // 获取日期参数
        $startDateRaw = $input->getOption('start-date');
        $endDateRaw = $input->getOption('end-date');

        if (!is_string($startDateRaw) || !is_string($endDateRaw)) {
            $io->error('日期参数必须为字符串类型');

            return Command::FAILURE;
        }

        $startDate = $startDateRaw;
        $endDate = $endDateRaw;

        // 获取或创建Publisher
        $publisher = $this->getOrCreatePublisher($publisherId, $token);

        $io->title('同步成果网交易数据');
        $io->section(sprintf('Publisher ID: %d, 日期范围: %s ~ %s', $publisherId, $startDate, $endDate));

        try {
            $this->syncTransactions($io, $publisher, $startDate, $endDate);
            $io->success('交易数据同步完成');

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

    private function syncTransactions(SymfonyStyle $io, Publisher $publisher, string $startDate, string $endDate): void
    {
        // 将日期转换为UTC时间格式
        $startTime = $startDate . ' 00:00:00';
        $endTime = $endDate . ' 23:59:59';

        $data = $this->apiClient->getTransactionReport($publisher, $startTime, $endTime);
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
            $transaction = $this->entityManager->find(Transaction::class, $transactionId);

            if (null === $transaction) {
                $transaction = new Transaction();
                $transaction->setPublisher($publisher);
                $transaction->setId($transactionId);
                $this->entityManager->persist($transaction);
            }

            /** @var array<string, mixed> $transactionData */
            $transaction->updateFromApiData($transactionData);
            ++$syncCount;
        }

        $this->entityManager->flush();
        $io->success(sprintf('同步了 %d 个交易记录', $syncCount));
    }
}
