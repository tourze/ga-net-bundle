<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\PromotionCampaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Enum\CampaignApplicationStatus;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Enum\PromotionType;
use Tourze\GaNetBundle\Enum\YesNoFlag;
use Tourze\GaNetBundle\Repository\PromotionCampaignRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(PromotionCampaignRepository::class)]
#[RunTestsInSeparateProcesses]
final class PromotionCampaignRepositoryTest extends AbstractRepositoryTestCase
{
    private static int $nextPublisherId = 50000;

    private static int $incrementCounter = 0;

    protected function getRepository(): PromotionCampaignRepository
    {
        return self::getService(PromotionCampaignRepository::class);
    }

    private function getUniquePublisherId(): int
    {
        return ++self::$nextPublisherId;
    }

    private function getUniqueTimestamp(): int
    {
        return time() + (++self::$incrementCounter);
    }

    protected function createNewEntity(): PromotionCampaign
    {
        // 使用唯一时间戳避免 ID 冲突
        $timestamp = $this->getUniqueTimestamp();

        // 创建测试发布商
        $publisher = new Publisher();
        $publisher->setPublisherId($timestamp);
        $publisher->setToken("test-token-{$timestamp}");
        $this->persistAndFlush($publisher);

        // 创建促销活动但不持久化
        $promotion = new PromotionCampaign();
        $promotion->setName('Test Promotion for Publisher ' . $publisher->getPublisherId());
        $promotion->setId($timestamp + 1000000); // 确保与Publisher ID不冲突
        $promotion->setPromotionType(PromotionType::DISCOUNT);
        $promotion->setName("Test Promotion Campaign {$timestamp}");
        $promotion->setStartTime('2024-01-01 00:00:00');
        $promotion->setEndTime('2024-12-31 23:59:59');
        $promotion->setMinCommission('0.00');

        return $promotion;
    }

    protected function onSetUp(): void
    {
        // Repository 测试设置方法
        // 清理EntityManager避免identity map冲突
        self::getEntityManager()->clear();
    }

    /**
     * 测试基本CRUD操作
     */
    public function testCrudOperations(): void
    {
        $repository = $this->getRepository();

        // 清理可能存在的测试数据
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\PromotionCampaign pc WHERE pc.id = :id')
            ->setParameter('id', 1001)
            ->execute()
        ;
        self::getEntityManager()->clear();

        // 创建测试发布商
        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建测试促销活动
        $promotion = new PromotionCampaign();
        $promotion->setName('Test Promotion for Publisher ' . $publisher->getPublisherId());
        $promotion->setId(1001);
        $promotion->setPromotionType(PromotionType::DISCOUNT);
        $promotion->setName('Test Promotion');
        $promotion->setStartTime('2024-01-01 00:00:00');
        $promotion->setEndTime('2024-12-31 23:59:59');
        $promotion->setMinCommission('0.00');

        // 测试保存
        $repository->save($promotion);
        $this->assertEntityPersisted($promotion);

        // 清理EntityManager以避免identity map冲突
        self::getEntityManager()->clear();

        // 测试查找
        $foundPromotion = $repository->find(1001);
        $this->assertNotNull($foundPromotion);
        $this->assertSame('Test Promotion', $foundPromotion->getTitle());
        $this->assertSame(PromotionType::DISCOUNT, $foundPromotion->getPromotionType());

        // 测试更新
        $foundPromotion->setName('Updated Promotion');
        $repository->save($foundPromotion);

        // 再次清理EntityManager
        self::getEntityManager()->clear();

        $updatedPromotion = $repository->find(1001);
        $this->assertNotNull($updatedPromotion);
        $this->assertSame('Updated Promotion', $updatedPromotion->getTitle());

        // 测试删除
        $repository->remove($updatedPromotion);
        $this->assertEntityNotExists(PromotionCampaign::class, 1001);
    }

    /**
     * 测试根据Publisher查找促销活动
     */
    public function testFindByPublisher(): void
    {
        $repository = $this->getRepository();

        // 创建两个发布商
        $publisher1 = new Publisher();
        $publisher1->setPublisherId(12345);
        $publisher1->setToken('token1');
        $publisher2 = new Publisher();
        $publisher2->setPublisherId(67890);
        $publisher2->setToken('token2');
        $this->persistAndFlush($publisher1);
        $this->persistAndFlush($publisher2);

        // 为第一个发布商创建促销活动
        $promotion1 = new PromotionCampaign();
        $promotion1->setId(2001);
        $promotion1->setPromotionType(PromotionType::DISCOUNT);
        $promotion1->setName('Promotion 1');
        $promotion1->setStartTime('2024-01-01 00:00:00');
        $promotion1->setEndTime('2024-12-31 23:59:59');
        $promotion1->setMinCommission('0.00');
        $this->persistAndFlush($promotion1);

        $promotion2 = new PromotionCampaign();
        $promotion2->setId(2002);
        $promotion2->setPromotionType(PromotionType::COUPON);
        $promotion2->setName('Promotion 2');
        $promotion2->setStartTime('2024-06-01 00:00:00');
        $promotion2->setEndTime('2024-06-30 23:59:59');
        $promotion2->setMinCommission('0.00');
        $this->persistAndFlush($promotion2);

        // 为第二个发布商创建促销活动
        $promotion3 = new PromotionCampaign();
        $promotion3->setId(2003);
        $promotion3->setPromotionType(PromotionType::DISCOUNT);
        $promotion3->setName('Promotion 3');
        $promotion3->setStartTime('2024-03-01 00:00:00');
        $promotion3->setEndTime('2024-03-31 23:59:59');
        $promotion3->setMinCommission('0.00');
        $this->persistAndFlush($promotion3);

        // 测试查找第一个发布商的促销活动
        $publisher1Promotions = $repository->findByPublisher($publisher1);
        $this->assertCount(2, $publisher1Promotions);

        // 验证按开始时间降序排序
        $this->assertSame(2002, $publisher1Promotions[0]->getId()); // 2024-06-01
        $this->assertSame(2001, $publisher1Promotions[1]->getId()); // 2024-01-01

        // 测试查找第二个发布商的促销活动
        $publisher2Promotions = $repository->findByPublisher($publisher2);
        $this->assertCount(1, $publisher2Promotions);
        $this->assertSame(2003, $publisher2Promotions[0]->getId());
    }

    /**
     * 测试根据活动查找促销
     */
    public function testFindByCampaign(): void
    {
        $repository = $this->getRepository();

        // 清理所有PromotionCampaign数据以避免DataFixtures干扰
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\PromotionCampaign pc')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建两个活动
        $campaign1 = new Campaign();
        $campaign1->setPublisher($publisher);
        $campaign1->setId(3001);
        $campaign1->setName('Campaign 1');
        $campaign1->setRegion('JPN');
        $campaign1->setUrl('https://campaign1.com');
        $campaign1->setCurrency(Currency::CNY);
        $campaign1->setStartTime('14-12-18');
        $campaign1->setCookieExpireTime(2592000);
        $campaign1->setSemPermitted(YesNoFlag::NO);
        $campaign1->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign1->setRebatePermitted(YesNoFlag::NO);
        $campaign1->setHasDatafeed(YesNoFlag::NO);
        $campaign1->setSupportWeapp(YesNoFlag::NO);
        $campaign1->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($campaign1);

        $campaign2 = new Campaign();
        $campaign2->setPublisher($publisher);
        $campaign2->setId(3002);
        $campaign2->setName('Campaign 2');
        $campaign2->setRegion('USA');
        $campaign2->setUrl('https://campaign2.com');
        $campaign2->setCurrency(Currency::USD);
        $campaign2->setStartTime('14-12-18');
        $campaign2->setCookieExpireTime(2592000);
        $campaign2->setSemPermitted(YesNoFlag::NO);
        $campaign2->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign2->setRebatePermitted(YesNoFlag::NO);
        $campaign2->setHasDatafeed(YesNoFlag::NO);
        $campaign2->setSupportWeapp(YesNoFlag::NO);
        $campaign2->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $this->persistAndFlush($campaign2);

        // 为第一个活动创建促销活动
        $promotion1 = new PromotionCampaign();
        $promotion1->setName('Promotion 1 for Publisher ' . $publisher->getPublisherId());
        $promotion1->setId(4001);
        $promotion1->setPromotionType(PromotionType::DISCOUNT);
        $promotion1->setName('Promotion for Campaign 1');
        $promotion1->setCampaign($campaign1);
        $promotion1->setStartTime('2024-01-01 00:00:00');
        $promotion1->setEndTime('2024-12-31 23:59:59');
        $promotion1->setMinCommission('0.00');
        $this->persistAndFlush($promotion1);

        $promotion2 = new PromotionCampaign();
        $promotion2->setName('Promotion 2 for Publisher ' . $publisher->getPublisherId());
        $promotion2->setId(4002);
        $promotion2->setPromotionType(PromotionType::COUPON);
        $promotion2->setName('Another Promotion for Campaign 1');
        $promotion2->setCampaign($campaign1);
        $promotion2->setStartTime('2024-06-01 00:00:00');
        $promotion2->setEndTime('2024-06-30 23:59:59');
        $promotion2->setMinCommission('0.00');
        $this->persistAndFlush($promotion2);

        // 为第二个活动创建促销活动
        $promotion3 = new PromotionCampaign();
        $promotion3->setName('Promotion 3 for Publisher ' . $publisher->getPublisherId());
        $promotion3->setId(4003);
        $promotion3->setPromotionType(PromotionType::DISCOUNT);
        $promotion3->setName('Promotion for Campaign 2');
        $promotion3->setCampaign($campaign2);
        $promotion3->setStartTime('2024-03-01 00:00:00');
        $promotion3->setEndTime('2024-03-31 23:59:59');
        $promotion3->setMinCommission('0.00');
        $this->persistAndFlush($promotion3);

        // 测试查找第一个活动的促销活动
        $campaign1Promotions = $repository->findByCampaign($campaign1);
        $this->assertCount(2, $campaign1Promotions);

        // 验证按开始时间降序排序
        $this->assertSame(4002, $campaign1Promotions[0]->getId()); // 2024-06-01
        $this->assertSame(4001, $campaign1Promotions[1]->getId()); // 2024-01-01

        // 测试查找第二个活动的促销活动
        $campaign2Promotions = $repository->findByCampaign($campaign2);
        $this->assertCount(1, $campaign2Promotions);
        $this->assertSame(4003, $campaign2Promotions[0]->getId());
    }

    /**
     * 测试根据促销类型查找
     */
    public function testFindByPromotionType(): void
    {
        $repository = $this->getRepository();

        // 清理所有PromotionCampaign数据以避免DataFixtures干扰
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\PromotionCampaign pc')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建不同类型的促销活动
        $discountPromotion = new PromotionCampaign();
        $discountPromotion->setName('Discount Promotion for Publisher ' . $publisher->getPublisherId());
        $discountPromotion->setId(5001);
        $discountPromotion->setPromotionType(PromotionType::DISCOUNT);
        $discountPromotion->setName('Discount Promotion');
        $discountPromotion->setStartTime('2024-01-01 00:00:00');
        $discountPromotion->setEndTime('2024-12-31 23:59:59');
        $discountPromotion->setMinCommission('0.00');
        $this->persistAndFlush($discountPromotion);

        $couponPromotion = new PromotionCampaign();
        $couponPromotion->setName('Coupon Promotion for Publisher ' . $publisher->getPublisherId());
        $couponPromotion->setId(5002);
        $couponPromotion->setPromotionType(PromotionType::COUPON);
        $couponPromotion->setName('Coupon Promotion');
        $couponPromotion->setStartTime('2024-06-01 00:00:00');
        $couponPromotion->setEndTime('2024-06-30 23:59:59');
        $couponPromotion->setMinCommission('0.00');
        $this->persistAndFlush($couponPromotion);

        // 测试按类型查找
        $discountPromotions = $repository->findByPromotionType(PromotionType::DISCOUNT->value);
        $this->assertCount(1, $discountPromotions);
        $this->assertSame(5001, $discountPromotions[0]->getId());

        $couponPromotions = $repository->findByPromotionType(PromotionType::COUPON->value);
        $this->assertCount(1, $couponPromotions);
        $this->assertSame(5002, $couponPromotions[0]->getId());

        // 测试按类型和发布商查找
        $discountPromotionsWithPublisher = $repository->findByPromotionType(PromotionType::DISCOUNT->value, $publisher);
        $this->assertCount(1, $discountPromotionsWithPublisher);
        $this->assertSame(5001, $discountPromotionsWithPublisher[0]->getId());

        // 测试查找不存在的类型
        $nonExistentPromotions = $repository->findByPromotionType(999);
        $this->assertCount(0, $nonExistentPromotions);
    }

    /**
     * 测试查找降价/打折类促销
     */
    public function testFindDiscountPromotions(): void
    {
        $repository = $this->getRepository();

        // 清理所有PromotionCampaign数据以避免DataFixtures干扰
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\PromotionCampaign pc')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建打折促销
        $discountPromotion = new PromotionCampaign();
        $discountPromotion->setName('Discount Promotion for Publisher ' . $publisher->getPublisherId());
        $discountPromotion->setId(6001);
        $discountPromotion->setPromotionType(PromotionType::DISCOUNT);
        $discountPromotion->setName('Discount Promotion');
        $discountPromotion->setStartTime('2024-01-01 00:00:00');
        $discountPromotion->setEndTime('2024-12-31 23:59:59');
        $discountPromotion->setMinCommission('0.00');
        $this->persistAndFlush($discountPromotion);

        // 创建优惠券促销
        $couponPromotion = new PromotionCampaign();
        $couponPromotion->setName('Coupon Promotion for Publisher ' . $publisher->getPublisherId());
        $couponPromotion->setId(6002);
        $couponPromotion->setPromotionType(PromotionType::COUPON);
        $couponPromotion->setName('Coupon Promotion');
        $couponPromotion->setStartTime('2024-06-01 00:00:00');
        $couponPromotion->setEndTime('2024-06-30 23:59:59');
        $couponPromotion->setMinCommission('0.00');
        $this->persistAndFlush($couponPromotion);

        // 测试查找打折促销
        $discountPromotions = $repository->findDiscountPromotions();
        $this->assertCount(1, $discountPromotions);
        $this->assertSame(6001, $discountPromotions[0]->getId());
        $this->assertSame(PromotionType::DISCOUNT, $discountPromotions[0]->getPromotionType());

        // 测试按发布商查找打折促销
        $discountPromotionsWithPublisher = $repository->findDiscountPromotions($publisher);
        $this->assertCount(1, $discountPromotionsWithPublisher);
        $this->assertSame(6001, $discountPromotionsWithPublisher[0]->getId());
    }

    /**
     * 测试查找优惠券类促销
     */
    public function testFindCouponPromotions(): void
    {
        $repository = $this->getRepository();

        // 清理所有PromotionCampaign数据以避免DataFixtures干扰
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\PromotionCampaign pc')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建打折促销
        $discountPromotion = new PromotionCampaign();
        $discountPromotion->setName('Discount Promotion for Publisher ' . $publisher->getPublisherId());
        $discountPromotion->setId(7001);
        $discountPromotion->setPromotionType(PromotionType::DISCOUNT);
        $discountPromotion->setName('Discount Promotion');
        $discountPromotion->setStartTime('2024-01-01 00:00:00');
        $discountPromotion->setEndTime('2024-12-31 23:59:59');
        $discountPromotion->setMinCommission('0.00');
        $this->persistAndFlush($discountPromotion);

        // 创建优惠券促销
        $couponPromotion = new PromotionCampaign();
        $couponPromotion->setName('Coupon Promotion for Publisher ' . $publisher->getPublisherId());
        $couponPromotion->setId(7002);
        $couponPromotion->setPromotionType(PromotionType::COUPON);
        $couponPromotion->setName('Coupon Promotion');
        $couponPromotion->setStartTime('2024-06-01 00:00:00');
        $couponPromotion->setEndTime('2024-06-30 23:59:59');
        $couponPromotion->setMinCommission('0.00');
        $this->persistAndFlush($couponPromotion);

        // 测试查找优惠券促销
        $couponPromotions = $repository->findCouponPromotions();
        $this->assertCount(1, $couponPromotions);
        $this->assertSame(7002, $couponPromotions[0]->getId());
        $this->assertSame(PromotionType::COUPON, $couponPromotions[0]->getPromotionType());

        // 测试按发布商查找优惠券促销
        $couponPromotionsWithPublisher = $repository->findCouponPromotions($publisher);
        $this->assertCount(1, $couponPromotionsWithPublisher);
        $this->assertSame(7002, $couponPromotionsWithPublisher[0]->getId());
    }

    /**
     * 测试查找当前活跃的促销活动
     */
    public function testFindActivePromotions(): void
    {
        $repository = $this->getRepository();

        // 清理所有PromotionCampaign数据以避免DataFixtures干扰
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\PromotionCampaign pc')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        $now = new \DateTime();

        // 创建活跃促销（当前时间在活动时间范围内）
        $activePromotion = new PromotionCampaign();
        $activePromotion->setName('Active Promotion for Publisher ' . $publisher->getPublisherId());
        $activePromotion->setId(8001);
        $activePromotion->setPromotionType(PromotionType::DISCOUNT);
        $activePromotion->setName('Active Promotion');
        $activePromotion->setStartTime((clone $now)->modify('-1 day')->format('Y-m-d H:i:s'));
        $activePromotion->setEndTime((clone $now)->modify('+2 days')->format('Y-m-d H:i:s'));
        $activePromotion->setMinCommission('0.00');
        $this->persistAndFlush($activePromotion);

        // 创建过期促销（已结束）
        $expiredPromotion = new PromotionCampaign();
        $expiredPromotion->setName('Expired Promotion for Publisher ' . $publisher->getPublisherId());
        $expiredPromotion->setId(8002);
        $expiredPromotion->setPromotionType(PromotionType::COUPON);
        $expiredPromotion->setName('Expired Promotion');
        $expiredPromotion->setStartTime((clone $now)->modify('-5 days')->format('Y-m-d H:i:s'));
        $expiredPromotion->setEndTime((clone $now)->modify('-1 day')->format('Y-m-d H:i:s'));
        $expiredPromotion->setMinCommission('0.00');
        $this->persistAndFlush($expiredPromotion);

        // 创建未开始促销
        $futurePromotion = new PromotionCampaign();
        $futurePromotion->setName('Future Promotion for Publisher ' . $publisher->getPublisherId());
        $futurePromotion->setId(8003);
        $futurePromotion->setPromotionType(PromotionType::DISCOUNT);
        $futurePromotion->setName('Future Promotion');
        $futurePromotion->setStartTime((clone $now)->modify('+1 day')->format('Y-m-d H:i:s'));
        $futurePromotion->setEndTime((clone $now)->modify('+5 days')->format('Y-m-d H:i:s'));
        $futurePromotion->setMinCommission('0.00');
        $this->persistAndFlush($futurePromotion);

        // 测试查找活跃促销
        $activePromotions = $repository->findActivePromotions();
        $this->assertCount(1, $activePromotions);
        $this->assertSame(8001, $activePromotions[0]->getId());
        $this->assertSame('Active Promotion', $activePromotions[0]->getTitle());

        // 测试按发布商查找活跃促销
        $activePromotionsWithPublisher = $repository->findActivePromotions($publisher);
        $this->assertCount(1, $activePromotionsWithPublisher);
        $this->assertSame(8001, $activePromotionsWithPublisher[0]->getId());
    }

    /**
     * 测试查找即将过期的促销活动
     */
    public function testFindExpiringSoonPromotions(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        $now = new \DateTime();

        // 创建即将过期的促销（7天内过期）
        $expiringSoonPromotion = new PromotionCampaign();
        $expiringSoonPromotion->setName('Expiring Soon Promotion for Publisher ' . $publisher->getPublisherId());
        $expiringSoonPromotion->setId(9001);
        $expiringSoonPromotion->setPromotionType(PromotionType::DISCOUNT);
        $expiringSoonPromotion->setName('Expiring Soon Promotion');
        $expiringSoonPromotion->setStartTime((clone $now)->modify('-10 days')->format('Y-m-d H:i:s'));
        $expiringSoonPromotion->setEndTime((clone $now)->modify('+3 days')->format('Y-m-d H:i:s'));
        $expiringSoonPromotion->setMinCommission('0.00');
        $this->persistAndFlush($expiringSoonPromotion);

        // 创建长期有效的促销（超过7天才过期）
        $longTermPromotion = new PromotionCampaign();
        $longTermPromotion->setName('Long Term Promotion for Publisher ' . $publisher->getPublisherId());
        $longTermPromotion->setId(9002);
        $longTermPromotion->setPromotionType(PromotionType::COUPON);
        $longTermPromotion->setName('Long Term Promotion');
        $longTermPromotion->setStartTime((clone $now)->modify('-10 days')->format('Y-m-d H:i:s'));
        $longTermPromotion->setEndTime((clone $now)->modify('+10 days')->format('Y-m-d H:i:s'));
        $longTermPromotion->setMinCommission('0.00');
        $this->persistAndFlush($longTermPromotion);

        // 创建已过期的促销
        $expiredPromotion = new PromotionCampaign();
        $expiredPromotion->setName('Expired Promotion for Publisher ' . $publisher->getPublisherId());
        $expiredPromotion->setId(9003);
        $expiredPromotion->setPromotionType(PromotionType::DISCOUNT);
        $expiredPromotion->setName('Expired Promotion');
        $expiredPromotion->setStartTime((clone $now)->modify('-15 days')->format('Y-m-d H:i:s'));
        $expiredPromotion->setEndTime((clone $now)->modify('-2 days')->format('Y-m-d H:i:s'));
        $expiredPromotion->setMinCommission('0.00');
        $this->persistAndFlush($expiredPromotion);

        // 测试查找即将过期的促销
        $expiringSoonPromotions = $repository->findExpiringSoonPromotions();
        $this->assertCount(1, $expiringSoonPromotions);
        $this->assertSame(9001, $expiringSoonPromotions[0]->getId());
        $this->assertSame('Expiring Soon Promotion', $expiringSoonPromotions[0]->getTitle());

        // 测试按发布商查找即将过期的促销
        $expiringSoonPromotionsWithPublisher = $repository->findExpiringSoonPromotions($publisher);
        $this->assertCount(1, $expiringSoonPromotionsWithPublisher);
        $this->assertSame(9001, $expiringSoonPromotionsWithPublisher[0]->getId());
    }

    /**
     * 测试根据优惠券码查找促销
     */
    public function testFindByCouponCode(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建有优惠券码的促销
        $couponPromotion = new PromotionCampaign();
        $couponPromotion->setName('Coupon Promotion for Publisher ' . $publisher->getPublisherId());
        $couponPromotion->setId(10001);
        $couponPromotion->setPromotionType(PromotionType::COUPON);
        $couponPromotion->setName('Coupon Promotion');
        $couponPromotion->setCouponCode('SAVE10');
        $couponPromotion->setStartTime('2024-01-01 00:00:00');
        $couponPromotion->setEndTime('2024-12-31 23:59:59');
        $couponPromotion->setMinCommission('0.00');
        $this->persistAndFlush($couponPromotion);

        // 创建没有优惠券码的促销
        $discountPromotion = new PromotionCampaign();
        $discountPromotion->setName('Discount Promotion for Publisher ' . $publisher->getPublisherId());
        $discountPromotion->setId(10002);
        $discountPromotion->setPromotionType(PromotionType::DISCOUNT);
        $discountPromotion->setName('Discount Promotion');
        $discountPromotion->setStartTime('2024-01-01 00:00:00');
        $discountPromotion->setEndTime('2024-12-31 23:59:59');
        $discountPromotion->setMinCommission('0.00');
        $this->persistAndFlush($discountPromotion);

        // 测试按优惠券码查找
        $foundPromotion = $repository->findByCouponCode('SAVE10');
        $this->assertNotNull($foundPromotion);
        $this->assertSame(10001, $foundPromotion->getId());
        $this->assertSame('SAVE10', $foundPromotion->getCouponCode());

        // 测试按优惠券码和发布商查找
        $foundPromotionWithPublisher = $repository->findByCouponCode('SAVE10', $publisher);
        $this->assertNotNull($foundPromotionWithPublisher);
        $this->assertSame(10001, $foundPromotionWithPublisher->getId());

        // 测试查找不存在的优惠券码
        $nonExistentPromotion = $repository->findByCouponCode('NONEXISTENT');
        $this->assertNull($nonExistentPromotion);
    }

    /**
     * 测试查找或创建促销活动
     */
    public function testFindOrCreate(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 测试创建新促销活动
        $promotion = $repository->findOrCreate(11001, $publisher);
        $this->assertSame(11001, $promotion->getId());
        $this->assertSame($publisher, $promotion->getPublisher());

        // 确保促销活动已持久化
        self::getEntityManager()->flush();
        $this->assertEntityPersisted($promotion);

        // 测试查找已存在的促销活动
        $foundPromotion = $repository->findOrCreate(11001, $publisher);
        $this->assertSame(11001, $foundPromotion->getId());
        $foundPromotionPublisher = $foundPromotion->getPublisher();
        $this->assertNotNull($foundPromotionPublisher, 'Found promotion publisher should not be null');
        $this->assertSame($publisher->getPublisherId(), $foundPromotionPublisher->getPublisherId());

        // 验证两个对象具有相同的属性
        $this->assertSame($promotion->getId(), $foundPromotion->getId());
        $originalPromotionPublisher = $promotion->getPublisher();
        $this->assertNotNull($originalPromotionPublisher, 'Original promotion publisher should not be null');
        $this->assertSame($originalPromotionPublisher->getPublisherId(), $foundPromotionPublisher->getPublisherId());
        $this->assertSame($promotion->getTitle(), $foundPromotion->getTitle());
    }
}
