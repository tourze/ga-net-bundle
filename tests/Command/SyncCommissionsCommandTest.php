<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\GaNetBundle\Command\SyncCommissionsCommand;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\CommissionRule;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Enum\CampaignApplicationStatus;
use Tourze\GaNetBundle\Enum\CommissionMode;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Enum\YesNoFlag;
use Tourze\GaNetBundle\Service\GaNetApiClient;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(SyncCommissionsCommand::class)]
#[RunTestsInSeparateProcesses]
class SyncCommissionsCommandTest extends AbstractCommandTestCase
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
        /** @var SyncCommissionsCommand $command */
        $command = self::getContainer()->get(SyncCommissionsCommand::class);
        $this->assertInstanceOf(SyncCommissionsCommand::class, $command);

        return new CommandTester($command);
    }

    public function testExecuteWithoutEnvironmentVariables(): void
    {
        // 清空环境变量
        unset($_ENV['GA_NET_PUBLISHER_ID'], $_ENV['GA_NET_WEBSITE_ID'], $_ENV['GA_NET_TOKEN']);

        $commandTester = $this->getCommandTester();
        $commandTester->execute([]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('必须在环境变量中设置 GA_NET_PUBLISHER_ID, GA_NET_WEBSITE_ID 和 GA_NET_TOKEN', $output);
    }

    public function testExecuteWithValidEnvironmentVariables(): void
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
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('同步成果网佣金规则', $output);
        $this->assertStringContainsString('Publisher ID: 12345, Website ID: 67890', $output);
    }

    public function testExecuteWithoutCampaigns(): void
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
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('没有找到活动数据，请先同步活动列表', $output);
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

    public function testSyncCommissions(): void
    {
        // 创建测试 Publisher
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);
        self::getEntityManager()->flush();

        // 创建测试 Campaign
        $campaign = new Campaign();
        $campaign->setPublisher($publisher);
        $campaign->setId(2914);
        $campaign->setRegion('JPN');
        $campaign->setName('Test Campaign');
        $campaign->setUrl('https://example.com');
        $campaign->setStartTime('2023-01-01 00:00:00');
        $campaign->setCurrency(Currency::CNY);
        $campaign->setCookieExpireTime(86400);
        $campaign->setSemPermitted(YesNoFlag::NO);
        $campaign->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign->setRebatePermitted(YesNoFlag::NO);
        $campaign->setHasDatafeed(YesNoFlag::NO);
        $campaign->setSupportWeapp(YesNoFlag::NO);
        $campaign->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        self::getEntityManager()->persist($campaign);
        self::getEntityManager()->flush();

        // 模拟 API 响应数据
        $mockApiData = [
            'id' => 1001,
            'name' => 'Test Commission Rule',
            'mode' => 1,
            'ratio' => '10.5',
            'currency' => 'CNY',
            'commission' => '100.00',
            'start_time' => '2023-01-01 00:00:00',
            'memo' => 'Test memo',
        ];

        // 创建 CommissionRule
        $commission = new CommissionRule();
        $commission->setCampaign($campaign);
        $commission->setId(1001);
        $commission->updateFromApiData($mockApiData);
        self::getEntityManager()->persist($commission);
        self::getEntityManager()->flush();

        // 验证 CommissionRule 被正确创建和更新
        $savedCommission = self::getEntityManager()->find(CommissionRule::class, 1001);
        $this->assertNotNull($savedCommission);
        $this->assertSame('Test Commission Rule', $savedCommission->getName());
        $this->assertSame(CommissionMode::PERCENTAGE, $savedCommission->getMode());
        $this->assertSame('10.5', $savedCommission->getRatio());
        $this->assertSame('CNY', $savedCommission->getCurrency());
        $this->assertSame('100.00', $savedCommission->getCommission());
        $this->assertSame('Test memo', $savedCommission->getMemo());
    }

    public function testCommissionRuleModeMethods(): void
    {
        // 创建测试 Publisher
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);
        self::getEntityManager()->flush();

        // 创建测试 Campaign
        $campaign = new Campaign();
        $campaign->setPublisher($publisher);
        $campaign->setId(2914);
        $campaign->setRegion('JPN');
        $campaign->setName('Test Campaign');
        $campaign->setUrl('https://example.com');
        $campaign->setStartTime('2023-01-01 00:00:00');
        $campaign->setCurrency(Currency::CNY);
        $campaign->setCookieExpireTime(86400);
        $campaign->setSemPermitted(YesNoFlag::NO);
        $campaign->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign->setRebatePermitted(YesNoFlag::NO);
        $campaign->setHasDatafeed(YesNoFlag::NO);
        $campaign->setSupportWeapp(YesNoFlag::NO);
        $campaign->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        self::getEntityManager()->persist($campaign);
        self::getEntityManager()->flush();

        // 测试百分比模式
        $percentageCommission = new CommissionRule();
        $percentageCommission->setCampaign($campaign);
        $percentageCommission->setId(1001);
        $percentageCommission->setName('Percentage Commission');
        $percentageCommission->setMode(CommissionMode::PERCENTAGE);
        $percentageCommission->setCurrency(Currency::CNY->value);
        $percentageCommission->setStartTime('2023-01-01 00:00:00');
        self::getEntityManager()->persist($percentageCommission);
        self::getEntityManager()->flush();

        $this->assertTrue($percentageCommission->isPercentageMode());
        $this->assertFalse($percentageCommission->isFixedMode());

        // 测试固定金额模式
        $fixedCommission = new CommissionRule();
        $fixedCommission->setCampaign($campaign);
        $fixedCommission->setId(1002);
        $fixedCommission->setName('Fixed Commission');
        $fixedCommission->setMode(CommissionMode::FIXED);
        $fixedCommission->setCurrency(Currency::CNY->value);
        $fixedCommission->setStartTime('2023-01-01 00:00:00');
        self::getEntityManager()->persist($fixedCommission);
        self::getEntityManager()->flush();

        $this->assertFalse($fixedCommission->isPercentageMode());
        $this->assertTrue($fixedCommission->isFixedMode());
    }
}
