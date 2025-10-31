<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\GaNetBundle\Command\SyncPromotionsCommand;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\PromotionCampaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Enum\CampaignApplicationStatus;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Enum\PromotionType;
use Tourze\GaNetBundle\Enum\YesNoFlag;
use Tourze\GaNetBundle\Service\GaNetApiClient;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(SyncPromotionsCommand::class)]
#[RunTestsInSeparateProcesses]
class SyncPromotionsCommandTest extends AbstractCommandTestCase
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
        /** @var SyncPromotionsCommand $command */
        $command = self::getContainer()->get(SyncPromotionsCommand::class);
        $this->assertInstanceOf(SyncPromotionsCommand::class, $command);

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
        $this->assertStringContainsString('同步成果网促销活动', $output);
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

    public function testSyncPromotions(): void
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
            'id' => 2001,
            'promotion_type' => PromotionType::DISCOUNT->value,
            'start_time' => '2023-01-01 00:00:00',
            'end_time' => '2023-12-31 23:59:59',
            'title' => 'Test Promotion',
            'image' => 'https://example.com/image.jpg',
            'url' => 'https://example.com/promo',
            'description' => 'Test promotion description',
            'coupon_code' => 'TESTCODE',
            'campaign_id' => 2914,
        ];

        // 创建 PromotionCampaign
        $promotion = new PromotionCampaign();
        $promotion->setName('Test Promotion');
        $promotion->setId(2001);
        $promotion->setMinCommission('0.00');
        $promotion->updateFromApiData($mockApiData);
        $promotion->setCampaign($campaign);
        self::getEntityManager()->persist($promotion);
        self::getEntityManager()->flush();

        // 验证 PromotionCampaign 被正确创建和更新
        $savedPromotion = self::getEntityManager()->find(PromotionCampaign::class, 2001);
        $this->assertNotNull($savedPromotion);
        $this->assertSame('Test Promotion', $savedPromotion->getTitle());
        $this->assertSame(PromotionType::DISCOUNT, $savedPromotion->getPromotionType());
        $this->assertSame('2023-01-01 00:00:00', $savedPromotion->getStartTime());
        $this->assertSame('2023-12-31 23:59:59', $savedPromotion->getEndTime());
        $this->assertSame('https://example.com/image.jpg', $savedPromotion->getImage());
        $this->assertSame('https://example.com/promo', $savedPromotion->getUrl());
        $this->assertSame('Test promotion description', $savedPromotion->getDescription());
        $this->assertSame('TESTCODE', $savedPromotion->getCouponCode());
        $this->assertSame(2914, $savedPromotion->getCampaignId());
    }

    public function testPromotionCampaignTypeMethods(): void
    {
        // 创建测试 Publisher
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);
        self::getEntityManager()->flush();

        // 测试打折类型
        $discountPromotion = new PromotionCampaign();
        $discountPromotion->setId(2001);
        $discountPromotion->setPromotionType(PromotionType::DISCOUNT);
        $discountPromotion->setStartTime('2023-01-01 00:00:00');
        $discountPromotion->setEndTime('2023-12-31 23:59:59');
        $discountPromotion->setName('Discount Promotion');
        $discountPromotion->setMinCommission('0.00');
        self::getEntityManager()->persist($discountPromotion);
        self::getEntityManager()->flush();

        $this->assertTrue($discountPromotion->isDiscountType());
        $this->assertFalse($discountPromotion->isCouponType());
        $this->assertSame('降价/打折', $discountPromotion->getPromotionTypeLabel());

        // 测试优惠券类型
        $couponPromotion = new PromotionCampaign();
        $couponPromotion->setId(2002);
        $couponPromotion->setPromotionType(PromotionType::COUPON);
        $couponPromotion->setStartTime('2023-01-01 00:00:00');
        $couponPromotion->setEndTime('2023-12-31 23:59:59');
        $couponPromotion->setName('Coupon Promotion');
        $couponPromotion->setMinCommission('0.00');
        self::getEntityManager()->persist($couponPromotion);
        self::getEntityManager()->flush();

        $this->assertFalse($couponPromotion->isDiscountType());
        $this->assertTrue($couponPromotion->isCouponType());
        $this->assertSame('优惠券', $couponPromotion->getPromotionTypeLabel());
    }

    public function testPromotionCampaignActiveStatus(): void
    {
        // 创建测试 Publisher
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);
        self::getEntityManager()->flush();

        // 测试已过期的促销活动
        $expiredPromotion = new PromotionCampaign();
        $expiredPromotion->setId(2001);
        $expiredPromotion->setPromotionType(PromotionType::DISCOUNT);
        $expiredPromotion->setStartTime('2022-01-01 00:00:00');
        $expiredPromotion->setEndTime('2022-12-31 23:59:59');
        $expiredPromotion->setName('Expired Promotion');
        $expiredPromotion->setMinCommission('0.00');
        self::getEntityManager()->persist($expiredPromotion);
        self::getEntityManager()->flush();

        $this->assertFalse($expiredPromotion->isActive());
        $this->assertFalse($expiredPromotion->isExpiringSoon());

        // 测试即将过期的促销活动
        $soonPromotion = new PromotionCampaign();
        $soonPromotion->setId(2002);
        $soonPromotion->setPromotionType(PromotionType::DISCOUNT);
        $soonPromotion->setStartTime(date('Y-m-d H:i:s', strtotime('-1 day')));
        $soonPromotion->setEndTime(date('Y-m-d H:i:s', strtotime('+3 days')));
        $soonPromotion->setName('Soon Expiring Promotion');
        $soonPromotion->setMinCommission('0.00');
        self::getEntityManager()->persist($soonPromotion);
        self::getEntityManager()->flush();

        $this->assertTrue($soonPromotion->isActive());
        $this->assertTrue($soonPromotion->isExpiringSoon());

        // 测试活跃的促销活动
        $activePromotion = new PromotionCampaign();
        $activePromotion->setId(2003);
        $activePromotion->setPromotionType(PromotionType::DISCOUNT);
        $activePromotion->setStartTime(date('Y-m-d H:i:s', strtotime('-1 day')));
        $activePromotion->setEndTime(date('Y-m-d H:i:s', strtotime('+30 days')));
        $activePromotion->setName('Active Promotion');
        $activePromotion->setMinCommission('0.00');
        self::getEntityManager()->persist($activePromotion);
        self::getEntityManager()->flush();

        $this->assertTrue($activePromotion->isActive());
        $this->assertFalse($activePromotion->isExpiringSoon());
    }
}
