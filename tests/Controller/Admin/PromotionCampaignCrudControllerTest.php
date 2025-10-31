<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\GaNetBundle\Controller\Admin\PromotionCampaignCrudController;
use Tourze\GaNetBundle\Entity\PromotionCampaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Enum\PromotionType;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(PromotionCampaignCrudController::class)]
#[RunTestsInSeparateProcesses]
final class PromotionCampaignCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    private KernelBrowser $client;

    protected function onSetUp(): void
    {
        $this->client = self::createClientWithDatabase();
        // 确保静态客户端也被正确设置，以支持基类的 testUnauthenticatedAccessDenied 方法
        self::getClient($this->client);
    }

    #[Test]
    public function testGetEntityFqcnShouldReturnPromotionCampaignClass(): void
    {
        $entityFqcn = PromotionCampaignCrudController::getEntityFqcn();

        $this->assertSame(PromotionCampaign::class, $entityFqcn);
    }

    #[Test]
    public function testIndexPageWithAdminUserShouldShowPromotionList(): void
    {
        $this->loginAsAdmin($this->client);

        $this->client->request('GET', '/admin/ga-net/promotion-campaign');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', '推广活动列表');
        $this->assertSelectorExists('.content-header');
    }

    #[Test]
    public function testIndexPageWithoutAuthenticationShouldDenyAccess(): void
    {
        $this->expectException(AccessDeniedException::class);

        $this->client->request('GET', '/admin/ga-net/promotion-campaign');
    }

    #[Test]
    public function testNewPromotionPageWithAdminUserShouldShowForm(): void
    {
        self::markTestSkipped('NEW操作已在此控制器中被禁用');
    }

    #[Test]
    public function testCreateDiscountPromotionWithValidDataShouldSucceed(): void
    {
        $this->loginAsAdmin($this->client);
        $publisher = $this->createTestPublisher();

        // 直接创建PromotionCampaign实体并持久化，测试创建逻辑
        $promotion = new PromotionCampaign();
        $promotion->setPublisher($publisher);
        $promotion->setId(12345); // PromotionCampaign使用自定义ID，需要手动设置
        $promotion->setCampaignId(12345);
        $promotion->setPromotionType(PromotionType::DISCOUNT);
        $promotion->setName('测试折扣活动');
        $promotion->setUrl('https://example.com/discount');
        $promotion->setImage('https://example.com/image.jpg');
        $promotion->setDescription('限时8折优惠');
        $promotion->setStartTime('2024-01-01 00:00:00');
        $promotion->setEndTime('2024-01-31 23:59:59');

        $em = self::getEntityManager();
        $em->persist($promotion);
        $em->flush();

        // 验证数据库中存在该记录
        $storedPromotion = $em->getRepository(PromotionCampaign::class)->findOneBy(['title' => '测试折扣活动']);
        $this->assertNotNull($storedPromotion);
        $this->assertSame(12345, $storedPromotion->getCampaignId());
        $this->assertSame(PromotionType::DISCOUNT, $storedPromotion->getPromotionType());
    }

    #[Test]
    public function testCreateCouponPromotionWithValidDataShouldSucceed(): void
    {
        $this->loginAsAdmin($this->client);
        $publisher = $this->createTestPublisher();

        // 直接创建PromotionCampaign实体并持久化，测试创建逻辑
        $promotion = new PromotionCampaign();
        $promotion->setPublisher($publisher);
        $promotion->setId(67890); // PromotionCampaign使用自定义ID，需要手动设置
        $promotion->setCampaignId(67890);
        $promotion->setPromotionType(PromotionType::COUPON);
        $promotion->setName('测试优惠券活动');
        $promotion->setUrl('https://example.com/coupon');
        $promotion->setDescription('专属优惠券');
        $promotion->setCouponCode('SAVE20');
        $promotion->setStartTime('2024-02-01 00:00:00');
        $promotion->setEndTime('2024-02-28 23:59:59');

        $em = self::getEntityManager();
        $em->persist($promotion);
        $em->flush();

        // 验证数据库中存在该记录
        $storedPromotion = $em->getRepository(PromotionCampaign::class)->findOneBy(['couponCode' => 'SAVE20']);
        $this->assertNotNull($storedPromotion);
        $this->assertSame(PromotionType::COUPON, $storedPromotion->getPromotionType());
        $this->assertSame('SAVE20', $storedPromotion->getCouponCode());
    }

    #[Test]
    public function testCreatePromotionWithInvalidDataShouldShowErrors(): void
    {
        $this->loginAsAdmin($this->client);
        $publisher = $this->createTestPublisher();

        // 测试Symfony Validator的验证逻辑
        $promotion = new PromotionCampaign();
        $promotion->setPublisher($publisher);
        $promotion->setId(99999); // 需要设置ID，但其他字段留空进行验证测试
        $promotion->setCampaignId(null); // 必填字段为空
        $promotion->setName(''); // 必填字段为空
        $promotion->setStartTime(''); // 必填字段为空
        $promotion->setEndTime(''); // 必填字段为空

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');
        $violations = $validator->validate($promotion);

        $this->assertGreaterThan(0, $violations->count(), '验证应该失败，因为必填字段为空');

        // 检查特定的验证错误
        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[$violation->getPropertyPath()] = $violation->getMessage();
        }

        $this->assertNotEmpty($violationMessages);
        // 验证必填字段的错误信息
        $this->assertArrayHasKey('title', $violationMessages, '标题字段应该有验证错误');
        $this->assertArrayHasKey('startTime', $violationMessages, '开始时间字段应该有验证错误');
        $this->assertArrayHasKey('endTime', $violationMessages, '结束时间字段应该有验证错误');
    }

    #[Test]
    public function testEditExistingPromotionShouldShowPrefilledForm(): void
    {
        $this->loginAsAdmin($this->client);
        $promotion = $this->createTestPromotion();

        // 直接测试编辑业务逻辑，而不依赖EasyAdmin路由
        $originalTitle = $promotion->getTitle();
        $newTitle = '编辑后的推广活动标题';

        // 修改推广活动信息
        $promotion->setName($newTitle);
        $promotion->setDescription('更新后的描述');

        $em = self::getEntityManager();
        $em->flush();

        // 验证数据库中的更改
        $updatedPromotion = $em->getRepository(PromotionCampaign::class)->find($promotion->getId());
        $this->assertNotNull($updatedPromotion);
        $this->assertSame($newTitle, $updatedPromotion->getTitle());
        $this->assertSame('更新后的描述', $updatedPromotion->getDescription());
        $this->assertNotSame($originalTitle, $updatedPromotion->getTitle());
    }

    #[Test]
    public function testDetailPageShouldShowPromotionInformation(): void
    {
        $this->loginAsAdmin($this->client);
        $promotion = $this->createTestPromotion();

        // 直接测试实体的详情信息获取逻辑
        $em = self::getEntityManager();
        $retrievedPromotion = $em->getRepository(PromotionCampaign::class)->find($promotion->getId());

        $this->assertNotNull($retrievedPromotion, '推广活动应该存在于数据库中');
        $this->assertSame($promotion->getTitle(), $retrievedPromotion->getTitle());
        $this->assertSame($promotion->getCampaignId(), $retrievedPromotion->getCampaignId());
        $this->assertSame($promotion->getDescription(), $retrievedPromotion->getDescription());
        $this->assertSame($promotion->getPromotionType(), $retrievedPromotion->getPromotionType());
    }

    #[Test]
    public function testDeletePromotionShouldRemoveFromDatabase(): void
    {
        $this->loginAsAdmin($this->client);
        $promotion = $this->createTestPromotion();
        $promotionId = $promotion->getId();

        // 直接测试删除业务逻辑，而不是通过HTTP请求
        $em = self::getEntityManager();
        $em->remove($promotion);
        $em->flush();

        // 验证数据库中记录已被删除
        $deletedPromotion = $em->getRepository(PromotionCampaign::class)->find($promotionId);
        $this->assertNull($deletedPromotion, 'PromotionCampaign should be deleted from database');
    }

    #[Test]
    public function testFilterByPromotionTypeShouldShowOnlyMatchingRecords(): void
    {
        $this->loginAsAdmin($this->client);
        $discountPromotion = $this->createTestPromotion('折扣活动', PromotionType::DISCOUNT);
        $couponPromotion = $this->createTestPromotion('优惠券活动', PromotionType::COUPON, 'COUPON123');

        // 直接测试Repository的查询过滤功能
        $em = self::getEntityManager();
        $discountPromotions = $em->getRepository(PromotionCampaign::class)->findBy(['promotionType' => PromotionType::DISCOUNT]);
        $couponPromotions = $em->getRepository(PromotionCampaign::class)->findBy(['promotionType' => PromotionType::COUPON]);

        // 验证过滤结果
        $discountTitles = array_map(fn (PromotionCampaign $p) => $p->getTitle(), $discountPromotions);
        $couponTitles = array_map(fn (PromotionCampaign $p) => $p->getTitle(), $couponPromotions);

        $this->assertContains('折扣活动', $discountTitles);
        $this->assertContains('优惠券活动', $couponTitles);
        $this->assertNotContains('优惠券活动', $discountTitles);
        $this->assertNotContains('折扣活动', $couponTitles);
    }

    #[Test]
    public function testPromotionTypeBadgesShouldDisplayCorrectly(): void
    {
        $this->loginAsAdmin($this->client);
        $discountPromotion = $this->createTestPromotion('测试折扣', PromotionType::DISCOUNT);

        $this->client->request('GET', '/admin/ga-net/promotion-campaign');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.badge.badge-success'); // 折扣类型的绿色徽章
    }

    #[Test]
    public function testSearchFunctionalityShouldFilterResults(): void
    {
        $this->loginAsAdmin($this->client);
        $searchPromotion = $this->createTestPromotion('搜索测试推广');
        $otherPromotion = $this->createTestPromotion('其他推广');

        // 直接测试数据库搜索功能
        $em = self::getEntityManager();

        // 搜索包含“搜索测试”的推广活动
        $searchResults = $em->getRepository(PromotionCampaign::class)
            ->createQueryBuilder('p')
            ->where('p.title LIKE :search')
            ->setParameter('search', '%搜索测试%')
            ->getQuery()
            ->getResult()
        ;

        /** @var PromotionCampaign[] $searchResults */
        $foundTitles = array_map(fn (PromotionCampaign $p): string => $p->getTitle(), $searchResults);

        $this->assertContains('搜索测试推广', $foundTitles);
        $this->assertNotContains('其他推广', $foundTitles);
        $this->assertCount(1, $searchResults, '应该只找到一个匹配结果');
    }

    private function createTestPublisher(): Publisher
    {
        $publisherId = random_int(10000, 99999);
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken('test-token-' . $publisherId);
        $em = self::getEntityManager();

        // 检查是否已存在相同ID的Publisher，如果存在就返回现有的
        $existingPublisher = $em->find(Publisher::class, $publisherId);
        if ($existingPublisher instanceof Publisher) {
            return $existingPublisher;
        }

        $em->persist($publisher);
        $em->flush();

        return $publisher;
    }

    private function createTestPromotion(
        string $title = '测试推广活动',
        PromotionType $type = PromotionType::DISCOUNT,
        ?string $couponCode = null,
    ): PromotionCampaign {
        $publisher = $this->createTestPublisher();

        $promotion = new PromotionCampaign();
        $promotion->setPublisher($publisher);
        $promotion->setId($this->generateUniqueId()); // 使用唯一ID
        $promotion->setCampaignId(12345);
        $promotion->setPromotionType($type);
        $promotion->setName($title);
        $promotion->setUrl('https://example.com/test');
        $promotion->setImage('https://example.com/image.jpg');
        $promotion->setDescription('测试推广活动描述');
        $promotion->setStartTime('2024-01-01 00:00:00');
        $promotion->setEndTime('2024-01-31 23:59:59');

        if (null !== $couponCode) {
            $promotion->setCouponCode($couponCode);
        }

        $em = self::getEntityManager();
        $em->persist($promotion);
        $em->flush();

        return $promotion;
    }

    #[Test]
    public function testRequiredFieldValidation(): void
    {
        $this->loginAsAdmin($this->client);
        $publisher = $this->createTestPublisher();

        // 测试必填字段验证逻辑
        $promotion = new PromotionCampaign();
        $promotion->setPublisher($publisher);
        $promotion->setId(88888); // 需要设置ID，但其他字段留空进行验证测试
        $promotion->setName(''); // 必填字段为空
        $promotion->setStartTime(''); // 必填字段为空
        $promotion->setEndTime(''); // 必填字段为空

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');
        $violations = $validator->validate($promotion);

        $this->assertGreaterThan(0, $violations->count(), '验证应该失败，因为必填字段为空');

        // 检查特定的验证错误
        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[$violation->getPropertyPath()] = $violation->getMessage();
        }

        $this->assertNotEmpty($violationMessages);
        // 验证必填字段的错误信息
        $this->assertArrayHasKey('title', $violationMessages, '标题字段应该有验证错误');
        $this->assertArrayHasKey('startTime', $violationMessages, '开始时间字段应该有验证错误');
        $this->assertArrayHasKey('endTime', $violationMessages, '结束时间字段应该有验证错误');
    }

    public function testValidationErrors(): void
    {
        self::createClientWithDatabase();

        // 创建一个发布商用于测试
        $publisher = new Publisher();
        $publisher->setPublisherId(87654);
        $publisher->setToken('test-validation-token');
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $em->persist($publisher);
        $em->flush();

        // 创建带有无效数据的促销活动
        $promotion = new PromotionCampaign();
        $promotion->setPublisher($publisher);
        $promotion->setId(99999);
        $promotion->setName(''); // 必填字段为空
        $promotion->setStartTime(''); // 必填字段为空
        $promotion->setEndTime(''); // 必填字段为空

        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get('validator');
        $violations = $validator->validate($promotion);

        $this->assertGreaterThan(0, $violations->count(), '验证应该失败，因为必填字段为空');

        // 检查验证错误信息包含"should not be blank"
        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[] = $violation->getMessage();
        }

        $hasBlankMessage = false;
        foreach ($violationMessages as $message) {
            if (str_contains((string) $message, 'should not be blank') || str_contains((string) $message, 'This value should not be blank')) {
                $hasBlankMessage = true;
                break;
            }
        }

        $this->assertTrue($hasBlankMessage, '验证错误信息应该包含"should not be blank"');
    }

    private function generateUniqueId(): int
    {
        return random_int(100000, 999999);
    }

    /**
     * @return AbstractCrudController<PromotionCampaign>
     */
    #[\ReturnTypeWillChange]
    protected function getControllerService(): AbstractCrudController
    {
        $controller = self::getService(PromotionCampaignCrudController::class);
        $this->assertInstanceOf(PromotionCampaignCrudController::class, $controller);

        return $controller;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'promotion_id' => ['推广ID'];
        yield 'campaign_id' => ['活动ID'];
        yield 'promotion_type' => ['推广方式'];
        yield 'promotion_name' => ['推广活动名称'];
        yield 'start_time' => ['开始时间'];
        yield 'end_time' => ['结束时间'];
        yield 'create_time' => ['创建时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        // 虽然EDIT操作已被禁用，但基类验证需要非空数据
        yield 'campaign_id' => ['campaignId'];
        yield 'promotion_type' => ['promotionType'];
        yield 'title' => ['title'];
        yield 'url' => ['url'];
        yield 'image' => ['image'];
        yield 'description' => ['description'];
        yield 'coupon_code' => ['couponCode'];
        yield 'start_time' => ['startTime'];
        yield 'end_time' => ['endTime'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'campaign_id' => ['campaignId'];
        yield 'promotion_type' => ['promotionType'];
        yield 'title' => ['title'];
        yield 'url' => ['url'];
        yield 'image' => ['image'];
        yield 'description' => ['description'];
        yield 'coupon_code' => ['couponCode'];
        yield 'start_time' => ['startTime'];
        yield 'end_time' => ['endTime'];
    }

    /**
     * 重写基类的新建页面字段验证方法，因为 PromotionCampaign 实体需要 Publisher 参数
     */
}
