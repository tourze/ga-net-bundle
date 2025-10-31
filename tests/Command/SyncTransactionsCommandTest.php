<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\GaNetBundle\Command\SyncTransactionsCommand;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\Transaction;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Enum\TransactionStatus;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(SyncTransactionsCommand::class)]
#[RunTestsInSeparateProcesses]
class SyncTransactionsCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // AbstractIntegrationTestCase 会自动清理数据库

        // 手动注册必要的服务
        $container = self::getContainer();

        // 注册 http_client 服务
        if (!$container->has('http_client')) {
            $container->set('http_client', $this->createMock('Symfony\Contracts\HttpClient\HttpClientInterface'));
        }

        // 注册 cache.app 服务
        if (!$container->has('cache.app')) {
            $container->set('cache.app', $this->createMock('Symfony\Contracts\Cache\CacheInterface'));
        }
    }

    protected function getCommandTester(): CommandTester
    {
        /** @var SyncTransactionsCommand $command */
        $command = self::getContainer()->get(SyncTransactionsCommand::class);
        $this->assertInstanceOf(SyncTransactionsCommand::class, $command);

        return new CommandTester($command);
    }

    public function testExecuteWithoutEnvironmentVariables(): void
    {
        // 清空环境变量
        unset($_ENV['GA_NET_PUBLISHER_ID'], $_ENV['GA_NET_TOKEN']);

        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('必须在环境变量中设置 GA_NET_PUBLISHER_ID 和 GA_NET_TOKEN', $output);
    }

    public function testExecuteWithValidEnvironmentVariables(): void
    {
        // 设置测试环境变量
        $_ENV['GA_NET_PUBLISHER_ID'] = '12345';
        $_ENV['GA_NET_TOKEN'] = 'test-token';

        // 创建测试 Publisher
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);
        self::getEntityManager()->flush();

        // Mock HTTP 客户端以返回模拟的 API 响应
        $httpClient = $this->createMock('Symfony\Contracts\HttpClient\HttpClientInterface');
        $mockResponse = $this->createMock('Symfony\Contracts\HttpClient\ResponseInterface');

        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getContent')->willReturn(json_encode([
            'response' => 200,
            'total_num' => 1,
            'transactions' => [
                [
                    'id' => 1,
                    'order_id' => 'TEST001',
                    'amount' => 100.50,
                    'status' => 'completed',
                    'date' => date('Y-m-d'),
                ],
            ],
        ]));

        $httpClient->method('request')->willReturn($mockResponse);

        // 替换容器中的 http_client 服务
        $container = self::getContainer();
        $container->set('http_client', $httpClient);

        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('同步成果网交易数据', $output);
        $this->assertStringContainsString('Publisher ID: 12345, 日期范围: ' . date('Y-m-d', strtotime('-7 days')) . ' ~ ' . date('Y-m-d'), $output);
    }

    public function testExecuteWithCustomDateRange(): void
    {
        // 设置测试环境变量
        $_ENV['GA_NET_PUBLISHER_ID'] = '12345';
        $_ENV['GA_NET_TOKEN'] = 'test-token';

        // 创建测试 Publisher
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);
        self::getEntityManager()->flush();

        // Mock HTTP 客户端以返回模拟的 API 响应
        $httpClient = $this->createMock('Symfony\Contracts\HttpClient\HttpClientInterface');
        $mockResponse = $this->createMock('Symfony\Contracts\HttpClient\ResponseInterface');

        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getContent')->willReturn(json_encode([
            'response' => 200,
            'total_num' => 1,
            'transactions' => [
                [
                    'id' => 1,
                    'order_id' => 'TEST001',
                    'amount' => 100.50,
                    'status' => 'completed',
                    'date' => '2023-06-15',
                ],
            ],
        ]));

        $httpClient->method('request')->willReturn($mockResponse);

        // 替换容器中的 http_client 服务
        $container = self::getContainer();
        $container->set('http_client', $httpClient);

        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            '--start-date' => '2023-06-01',
            '--end-date' => '2023-06-30',
        ]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('同步成果网交易数据', $output);
        $this->assertStringContainsString('Publisher ID: 12345, 日期范围: 2023-06-01 ~ 2023-06-30', $output);
    }

    public function testGetOrCreatePublisherCreatesNew(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(99999);
        $publisher->setToken('new-token');
        self::getEntityManager()->persist($publisher);
        self::getEntityManager()->flush();

        // 验证 Publisher 被创建
        $savedPublisher = self::getEntityManager()->find(Publisher::class, 99999);
        $this->assertNotNull($savedPublisher);
        $this->assertSame('new-token', $savedPublisher->getToken());
    }

    public function testGetOrCreatePublisherUpdatesExisting(): void
    {
        // 先创建一个 Publisher
        $publisher = new Publisher();
        $publisher->setPublisherId(88888);
        $publisher->setToken('old-token');
        self::getEntityManager()->persist($publisher);
        self::getEntityManager()->flush();

        // 清除实体管理器缓存
        self::getEntityManager()->clear();

        // 重新获取并更新
        $updatedPublisher = self::getEntityManager()->find(Publisher::class, 88888);
        $this->assertNotNull($updatedPublisher);
        $updatedPublisher->setToken('updated-token');
        self::getEntityManager()->flush();

        // 验证更新成功
        self::getEntityManager()->clear();
        $finalPublisher = self::getEntityManager()->find(Publisher::class, 88888);
        $this->assertNotNull($finalPublisher);
        $this->assertSame('updated-token', $finalPublisher->getToken());
    }

    public function testSyncTransactions(): void
    {
        // 创建测试 Publisher
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);
        self::getEntityManager()->flush();

        // 模拟 API 响应数据
        $mockApiData = [
            'id' => 4001,
            'memo' => 'Test transaction memo',
            'order_id' => 'ORDER123456',
            'website_id' => 67890,
            'total_price' => '1000.00',
            'campaign_id' => 2914,
            'campaign_name' => 'Test Campaign',
            'total_commission' => '100.00',
            'order_time' => '2023-06-15 10:30:00',
            'order_status' => TransactionStatus::CONFIRMED->value,
            'currency' => 'CNY',
            'tag' => 'test-tag',
            'category_id' => '123',
            'category_name' => 'Test Category',
            'item_quantity' => 2,
            'item_name' => 'Test Product',
            'original_currency' => 'CNY',
            'original_total_price' => '1000.00',
            'balance_time' => '2023-06',
        ];

        // 创建 Transaction
        $transaction = new Transaction();
        $transaction->setPublisher($publisher);
        $transaction->setId(4001);
        $transaction->updateFromApiData($mockApiData);
        self::getEntityManager()->persist($transaction);
        self::getEntityManager()->flush();

        // 验证 Transaction 被正确创建和更新
        $savedTransaction = self::getEntityManager()->find(Transaction::class, 4001);
        $this->assertNotNull($savedTransaction);
        $this->assertSame('Test transaction memo', $savedTransaction->getMemo());
        $this->assertSame('ORDER123456', $savedTransaction->getOrderId());
        $this->assertSame(67890, $savedTransaction->getWebsiteId());
        $this->assertSame('1000.00', $savedTransaction->getTotalPrice());
        $this->assertSame(2914, $savedTransaction->getCampaignId());
        $this->assertSame('Test Campaign', $savedTransaction->getCampaignName());
        $this->assertSame('100.00', $savedTransaction->getTotalCommission());
        $this->assertSame('2023-06-15 10:30:00', $savedTransaction->getOrderTime());
        $this->assertSame(TransactionStatus::CONFIRMED, $savedTransaction->getOrderStatus());
        $this->assertSame(Currency::CNY, $savedTransaction->getCurrency());
        $this->assertSame('test-tag', $savedTransaction->getTag());
        $this->assertSame('123', $savedTransaction->getCategoryId());
        $this->assertSame('Test Category', $savedTransaction->getCategoryName());
        $this->assertSame(2, $savedTransaction->getItemQuantity());
        $this->assertSame('Test Product', $savedTransaction->getItemName());
        $this->assertSame(Currency::CNY, $savedTransaction->getOriginalCurrency());
        $this->assertSame('1000.00', $savedTransaction->getOriginalTotalPrice());
        $this->assertSame('2023-06', $savedTransaction->getBalanceTime());
    }

    public function testTransactionStatusMethods(): void
    {
        // 创建测试 Publisher
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);
        self::getEntityManager()->flush();

        // 测试待认证状态
        $pendingTransaction = new Transaction();
        $pendingTransaction->setPublisher($publisher);
        $pendingTransaction->setId(4001);
        $pendingTransaction->setOrderId('ORDER123456');
        $pendingTransaction->setWebsiteId(67890);
        $pendingTransaction->setTotalPrice('1000.00');
        $pendingTransaction->setCampaignName('Test Campaign');
        $pendingTransaction->setTotalCommission('100.00');
        $pendingTransaction->setOrderTime('2023-06-15 10:30:00');
        $pendingTransaction->setOrderStatus(TransactionStatus::PENDING);
        $pendingTransaction->setCurrency(Currency::CNY);
        $pendingTransaction->setItemQuantity(1);
        $pendingTransaction->setItemName('Test Product');
        $pendingTransaction->setOriginalCurrency(Currency::CNY);
        $pendingTransaction->setOriginalTotalPrice('1000.00');
        self::getEntityManager()->persist($pendingTransaction);
        self::getEntityManager()->flush();

        $this->assertTrue($pendingTransaction->isPending());
        $this->assertFalse($pendingTransaction->isConfirmed());
        $this->assertFalse($pendingTransaction->isRejected());
        $this->assertFalse($pendingTransaction->isSettled());
        $this->assertSame('待认证', $pendingTransaction->getStatusLabel());

        // 测试已认证状态
        $confirmedTransaction = new Transaction();
        $confirmedTransaction->setPublisher($publisher);
        $confirmedTransaction->setId(4002);
        $confirmedTransaction->setOrderId('ORDER123457');
        $confirmedTransaction->setWebsiteId(67890);
        $confirmedTransaction->setTotalPrice('2000.00');
        $confirmedTransaction->setCampaignName('Test Campaign 2');
        $confirmedTransaction->setTotalCommission('200.00');
        $confirmedTransaction->setOrderTime('2023-06-15 11:30:00');
        $confirmedTransaction->setOrderStatus(TransactionStatus::CONFIRMED);
        $confirmedTransaction->setCurrency(Currency::CNY);
        $confirmedTransaction->setItemQuantity(1);
        $confirmedTransaction->setItemName('Test Product 2');
        $confirmedTransaction->setOriginalCurrency(Currency::CNY);
        $confirmedTransaction->setOriginalTotalPrice('2000.00');
        self::getEntityManager()->persist($confirmedTransaction);
        self::getEntityManager()->flush();

        $this->assertFalse($confirmedTransaction->isPending());
        $this->assertTrue($confirmedTransaction->isConfirmed());
        $this->assertFalse($confirmedTransaction->isRejected());
        $this->assertFalse($confirmedTransaction->isSettled());
        $this->assertSame('已认证', $confirmedTransaction->getStatusLabel());

        // 测试拒绝状态
        $rejectedTransaction = new Transaction();
        $rejectedTransaction->setPublisher($publisher);
        $rejectedTransaction->setId(4003);
        $rejectedTransaction->setOrderId('ORDER123458');
        $rejectedTransaction->setWebsiteId(67890);
        $rejectedTransaction->setTotalPrice('3000.00');
        $rejectedTransaction->setCampaignName('Test Campaign 3');
        $rejectedTransaction->setTotalCommission('300.00');
        $rejectedTransaction->setOrderTime('2023-06-15 12:30:00');
        $rejectedTransaction->setOrderStatus(TransactionStatus::REJECTED);
        $rejectedTransaction->setCurrency(Currency::CNY);
        $rejectedTransaction->setItemQuantity(1);
        $rejectedTransaction->setItemName('Test Product 3');
        $rejectedTransaction->setOriginalCurrency(Currency::CNY);
        $rejectedTransaction->setOriginalTotalPrice('3000.00');
        self::getEntityManager()->persist($rejectedTransaction);
        self::getEntityManager()->flush();

        $this->assertFalse($rejectedTransaction->isPending());
        $this->assertFalse($rejectedTransaction->isConfirmed());
        $this->assertTrue($rejectedTransaction->isRejected());
        $this->assertFalse($rejectedTransaction->isSettled());
        $this->assertSame('拒绝', $rejectedTransaction->getStatusLabel());

        // 测试已结算状态
        $settledTransaction = new Transaction();
        $settledTransaction->setPublisher($publisher);
        $settledTransaction->setId(4004);
        $settledTransaction->setOrderId('ORDER123459');
        $settledTransaction->setWebsiteId(67890);
        $settledTransaction->setTotalPrice('4000.00');
        $settledTransaction->setCampaignName('Test Campaign 4');
        $settledTransaction->setTotalCommission('400.00');
        $settledTransaction->setOrderTime('2023-06-15 13:30:00');
        $settledTransaction->setOrderStatus(TransactionStatus::CONFIRMED);
        $settledTransaction->setCurrency(Currency::CNY);
        $settledTransaction->setItemQuantity(1);
        $settledTransaction->setItemName('Test Product 4');
        $settledTransaction->setOriginalCurrency(Currency::CNY);
        $settledTransaction->setOriginalTotalPrice('4000.00');
        $settledTransaction->setBalanceTime('2023-06');
        self::getEntityManager()->persist($settledTransaction);
        self::getEntityManager()->flush();

        $this->assertFalse($settledTransaction->isPending());
        $this->assertTrue($settledTransaction->isConfirmed());
        $this->assertFalse($settledTransaction->isRejected());
        $this->assertTrue($settledTransaction->isSettled());
        $this->assertSame('已认证', $settledTransaction->getStatusLabel());
    }

    public function testOptionStartDate(): void
    {
        // 设置测试环境变量
        $_ENV['GA_NET_PUBLISHER_ID'] = '12345';
        $_ENV['GA_NET_WEBSITE_ID'] = '67890';
        $_ENV['GA_NET_TOKEN'] = 'test-token';

        // 创建测试 Publisher
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);
        self::getEntityManager()->flush();

        // Mock HTTP 客户端以返回模拟的 API 响应
        $httpClient = $this->createMock('Symfony\Contracts\HttpClient\HttpClientInterface');
        $mockResponse = $this->createMock('Symfony\Contracts\HttpClient\ResponseInterface');

        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getContent')->willReturn(json_encode([
            'response' => 200,
            'total_num' => 0,
            'transactions' => [],
        ]));

        $httpClient->method('request')->willReturn($mockResponse);

        // 替换容器中的 http_client 服务
        $container = self::getContainer();
        $container->set('http_client', $httpClient);

        $commandTester = $this->getCommandTester();

        // 测试自定义开始日期选项
        $commandTester->execute(['--start-date' => '2023-06-01']);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('同步成果网交易数据', $output);
        $this->assertStringContainsString('日期范围: 2023-06-01', $output);
    }

    public function testOptionEndDate(): void
    {
        // 设置测试环境变量
        $_ENV['GA_NET_PUBLISHER_ID'] = '12345';
        $_ENV['GA_NET_WEBSITE_ID'] = '67890';
        $_ENV['GA_NET_TOKEN'] = 'test-token';

        // 创建测试 Publisher
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);
        self::getEntityManager()->flush();

        // Mock HTTP 客户端以返回模拟的 API 响应
        $httpClient = $this->createMock('Symfony\Contracts\HttpClient\HttpClientInterface');
        $mockResponse = $this->createMock('Symfony\Contracts\HttpClient\ResponseInterface');

        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getContent')->willReturn(json_encode([
            'response' => 200,
            'total_num' => 0,
            'transactions' => [],
        ]));

        $httpClient->method('request')->willReturn($mockResponse);

        // 替换容器中的 http_client 服务
        $container = self::getContainer();
        $container->set('http_client', $httpClient);

        $commandTester = $this->getCommandTester();

        // 测试自定义结束日期选项
        $commandTester->execute(['--end-date' => '2023-06-30']);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('同步成果网交易数据', $output);
        $this->assertStringContainsString('~ 2023-06-30', $output);
    }
}
