<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\GaNetBundle\Command\SyncSettlementsCommand;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\Settlement;
use Tourze\GaNetBundle\Enum\SettlementStatus;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(SyncSettlementsCommand::class)]
#[RunTestsInSeparateProcesses]
class SyncSettlementsCommandTest extends AbstractCommandTestCase
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
        /** @var SyncSettlementsCommand $command */
        $command = self::getContainer()->get(SyncSettlementsCommand::class);
        $this->assertInstanceOf(SyncSettlementsCommand::class, $command);

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
            'settlements' => [
                [
                    'id' => 1,
                    'month' => date('Y-m', strtotime('-1 month')),
                    'amount' => 100.50,
                    'status' => 'completed',
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
        $this->assertStringContainsString('同步成果网结算数据', $output);
        $this->assertStringContainsString('Publisher ID: 12345, 结算月份: ' . date('Y-m', strtotime('-1 month')), $output);
    }

    public function testExecuteWithCustomSettlementMonth(): void
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
            'settlements' => [
                [
                    'id' => 1,
                    'month' => '2023-06',
                    'amount' => 100.50,
                    'status' => 'completed',
                ],
            ],
        ]));

        $httpClient->method('request')->willReturn($mockResponse);

        // 替换容器中的 http_client 服务
        $container = self::getContainer();
        $container->set('http_client', $httpClient);

        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            '--settlement-month' => '2023-06',
        ]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('同步成果网结算数据', $output);
        $this->assertStringContainsString('Publisher ID: 12345, 结算月份: 2023-06', $output);
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

    public function testSyncSettlements(): void
    {
        // 创建测试 Publisher
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);
        self::getEntityManager()->flush();

        // 模拟 API 响应数据
        $mockApiData = [
            'id' => 3001,
            'order_id' => 'ORDER123456',
            'website_id' => 67890,
            'total_price' => '1000.00',
            'campaign_id' => 2914,
            'campaign_name' => 'Test Campaign',
            'total_commission' => '100.00',
            'order_time' => '2023-06-15 10:30:00',
            'order_status' => SettlementStatus::APPROVED->value,
            'currency' => 'CNY',
            'tag' => 'test-tag',
            'balance_time' => '2023-06',
            'category_id' => '123',
            'category_name' => 'Test Category',
            'item_quantity' => 2,
            'item_name' => 'Test Product',
            'original_currency' => 'CNY',
            'original_total_price' => '1000.00',
        ];

        // 创建 Settlement
        $settlement = new Settlement();
        $settlement->setPublisher($publisher);
        $settlement->setId(3001);
        $settlement->updateFromApiData($mockApiData);
        self::getEntityManager()->persist($settlement);
        self::getEntityManager()->flush();

        // 验证 Settlement 被正确创建和更新
        $savedSettlement = self::getEntityManager()->find(Settlement::class, 3001);
        $this->assertNotNull($savedSettlement);
        $this->assertSame('ORDER123456', $savedSettlement->getOrderId());
        $this->assertSame(67890, $savedSettlement->getWebsiteId());
        $this->assertSame('1000.00', $savedSettlement->getTotalPrice());
        $this->assertSame(2914, $savedSettlement->getCampaignId());
        $this->assertSame('Test Campaign', $savedSettlement->getCampaignName());
        $this->assertSame('100.00', $savedSettlement->getTotalCommission());
        $this->assertSame('2023-06-15 10:30:00', $savedSettlement->getOrderTime());
        $this->assertSame(SettlementStatus::APPROVED, $savedSettlement->getOrderStatus());
        $this->assertSame('CNY', $savedSettlement->getCurrency());
        $this->assertSame('test-tag', $savedSettlement->getTag());
        $this->assertSame('2023-06', $savedSettlement->getBalanceTime());
        $this->assertSame('123', $savedSettlement->getCategoryId());
        $this->assertSame('Test Category', $savedSettlement->getCategoryName());
        $this->assertSame(2, $savedSettlement->getItemQuantity());
        $this->assertSame('Test Product', $savedSettlement->getItemName());
        $this->assertSame('CNY', $savedSettlement->getOriginalCurrency());
        $this->assertSame('1000.00', $savedSettlement->getOriginalTotalPrice());
    }

    public function testSettlementStatusMethods(): void
    {
        // 创建测试 Publisher
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);
        self::getEntityManager()->flush();

        // 测试待认证状态
        $pendingSettlement = new Settlement();
        $pendingSettlement->setPublisher($publisher);
        $pendingSettlement->setId(3001);
        $pendingSettlement->setOrderId('ORDER123456');
        $pendingSettlement->setWebsiteId(67890);
        $pendingSettlement->setTotalPrice('1000.00');
        $pendingSettlement->setCampaignName('Test Campaign');
        $pendingSettlement->setTotalCommission('100.00');
        $pendingSettlement->setOrderTime('2023-06-15 10:30:00');
        $pendingSettlement->setOrderStatus(SettlementStatus::PENDING);
        $pendingSettlement->setCurrency('CNY');
        $pendingSettlement->setBalanceTime('2023-06');
        $pendingSettlement->setItemQuantity(1);
        $pendingSettlement->setItemName('Test Product');
        $pendingSettlement->setOriginalCurrency('CNY');
        $pendingSettlement->setOriginalTotalPrice('1000.00');
        self::getEntityManager()->persist($pendingSettlement);
        self::getEntityManager()->flush();

        $this->assertTrue($pendingSettlement->isPending());
        $this->assertFalse($pendingSettlement->isApproved());
        $this->assertFalse($pendingSettlement->isRejected());
        $this->assertSame('待认证', $pendingSettlement->getStatusLabel());

        // 测试已通过状态
        $approvedSettlement = new Settlement();
        $approvedSettlement->setPublisher($publisher);
        $approvedSettlement->setId(3002);
        $approvedSettlement->setOrderId('ORDER123457');
        $approvedSettlement->setWebsiteId(67890);
        $approvedSettlement->setTotalPrice('2000.00');
        $approvedSettlement->setCampaignName('Test Campaign 2');
        $approvedSettlement->setTotalCommission('200.00');
        $approvedSettlement->setOrderTime('2023-06-15 11:30:00');
        $approvedSettlement->setOrderStatus(SettlementStatus::APPROVED);
        $approvedSettlement->setCurrency('CNY');
        $approvedSettlement->setBalanceTime('2023-06');
        $approvedSettlement->setItemQuantity(1);
        $approvedSettlement->setItemName('Test Product 2');
        $approvedSettlement->setOriginalCurrency('CNY');
        $approvedSettlement->setOriginalTotalPrice('2000.00');
        self::getEntityManager()->persist($approvedSettlement);
        self::getEntityManager()->flush();

        $this->assertFalse($approvedSettlement->isPending());
        $this->assertTrue($approvedSettlement->isApproved());
        $this->assertFalse($approvedSettlement->isRejected());
        $this->assertSame('已通过', $approvedSettlement->getStatusLabel());

        // 测试已拒绝状态
        $rejectedSettlement = new Settlement();
        $rejectedSettlement->setPublisher($publisher);
        $rejectedSettlement->setId(3003);
        $rejectedSettlement->setOrderId('ORDER123458');
        $rejectedSettlement->setWebsiteId(67890);
        $rejectedSettlement->setTotalPrice('3000.00');
        $rejectedSettlement->setCampaignName('Test Campaign 3');
        $rejectedSettlement->setTotalCommission('300.00');
        $rejectedSettlement->setOrderTime('2023-06-15 12:30:00');
        $rejectedSettlement->setOrderStatus(SettlementStatus::REJECTED);
        $rejectedSettlement->setCurrency('CNY');
        $rejectedSettlement->setBalanceTime('2023-06');
        $rejectedSettlement->setItemQuantity(1);
        $rejectedSettlement->setItemName('Test Product 3');
        $rejectedSettlement->setOriginalCurrency('CNY');
        $rejectedSettlement->setOriginalTotalPrice('3000.00');
        self::getEntityManager()->persist($rejectedSettlement);
        self::getEntityManager()->flush();

        $this->assertFalse($rejectedSettlement->isPending());
        $this->assertFalse($rejectedSettlement->isApproved());
        $this->assertTrue($rejectedSettlement->isRejected());
        $this->assertSame('已拒绝', $rejectedSettlement->getStatusLabel());
    }

    public function testOptionSettlementMonth(): void
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

        $commandTester = $this->getCommandTester();

        // 测试自定义结算月份选项
        $commandTester->execute(['--settlement-month' => '2023-06']);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('同步成果网结算数据', $output);
        $this->assertStringContainsString('结算月份: 2023-06', $output);
    }
}
