<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\Settlement;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Enum\SettlementStatus;
use Tourze\GaNetBundle\Repository\SettlementRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(SettlementRepository::class)]
#[RunTestsInSeparateProcesses]
final class SettlementRepositoryTest extends AbstractRepositoryTestCase
{
    private static int $nextPublisherId = 60000;

    private static int $nextSettlementId = 90000;

    private static int $incrementCounter = 0;

    protected function getRepository(): SettlementRepository
    {
        return self::getService(SettlementRepository::class);
    }

    private function getUniquePublisherId(): int
    {
        return ++self::$nextPublisherId;
    }

    private function getUniqueSettlementId(): int
    {
        return ++self::$nextSettlementId;
    }

    private function getUniqueTimestamp(): int
    {
        return time() + (++self::$incrementCounter);
    }

    protected function createNewEntity(): Settlement
    {
        // 使用唯一时间戳避免 ID 冲突
        $timestamp = $this->getUniqueTimestamp();

        // 创建测试发布商
        $publisher = new Publisher();
        $publisher->setPublisherId($timestamp);
        $publisher->setToken("test-token-{$timestamp}");
        $this->persistAndFlush($publisher);

        // 创建结算数据但不持久化
        $settlement = new Settlement();
        $settlement->setPublisher($publisher);
        // 使用非常大的唯一ID以避免与任何其他测试冲突
        $uniqueId = 100000 + $timestamp;
        $settlement->setId($uniqueId);
        $settlement->setOrderId("ORD-TEST-{$timestamp}");
        $settlement->setWebsiteId(1);
        $settlement->setTotalPrice('100.00');
        $settlement->setCampaignName("Test Settlement Campaign {$timestamp}");
        $settlement->setBalanceTime('2024-01');
        $settlement->setOrderTime('2024-01-01 12:00:00');
        $settlement->setOrderStatus(SettlementStatus::PENDING);
        $settlement->setTotalCommission('5.00');
        $settlement->setCurrency(Currency::CNY->value);
        $settlement->setItemQuantity(1);
        $settlement->setItemName("Test Settlement Product {$timestamp}");
        $settlement->setOriginalCurrency('CNY');
        $settlement->setOriginalTotalPrice('100.00');

        return $settlement;
    }

    protected function onSetUp(): void
    {
        // Repository 测试设置方法
        // 先清理EntityManager避免identity map冲突
        self::getEntityManager()->clear();

        // 删除所有测试数据范围的Settlement（ID >= 60000 或特定测试ID范围）
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\Settlement s WHERE s.id >= 60000')
            ->execute()
        ;
        // 删除所有测试数据范围的Publisher（publisher_id >= 60000 或特定测试ID范围）
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\Publisher p WHERE p.publisher_id >= 60000')
            ->execute()
        ;

        // 清理所有缓存和identity map
        self::getEntityManager()->clear();

        // 重置静态计数器
        self::$incrementCounter = 0;
    }

    /**
     * 测试基本CRUD操作
     */
    public function testCrudOperations(): void
    {
        $repository = $this->getRepository();

        // 确保清理EntityManager避免任何缓存冲突
        self::getEntityManager()->clear();

        // 直接创建测试实体，不使用createNewEntity以避免ID冲突
        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        $settlementId = $this->getUniqueSettlementId();
        $settlement = new Settlement();
        $settlement->setPublisher($publisher);
        $settlement->setId($settlementId);
        $settlement->setOrderId('ORD-2024-001');
        $settlement->setWebsiteId(1);
        $settlement->setTotalPrice('100.00');
        $settlement->setCampaignName('Test Campaign');
        $settlement->setBalanceTime('2024-01');
        $settlement->setOrderTime('2024-01-01 12:00:00');
        $settlement->setOrderStatus(SettlementStatus::PENDING);
        $settlement->setTotalCommission('5.00');
        $settlement->setCurrency(Currency::CNY->value);
        $settlement->setItemQuantity(1);
        $settlement->setItemName('Test Product');
        $settlement->setOriginalCurrency('CNY');
        $settlement->setOriginalTotalPrice('100.00');

        // 测试保存
        $repository->save($settlement);
        $this->assertEntityPersisted($settlement);

        // 清理EntityManager避免identity map问题
        self::getEntityManager()->clear();

        // 测试查找
        $foundSettlement = $repository->find($settlementId);
        $this->assertNotNull($foundSettlement);
        $this->assertSame('ORD-2024-001', $foundSettlement->getOrderId());
        $this->assertSame(SettlementStatus::PENDING, $foundSettlement->getOrderStatus());

        // 测试更新
        $foundSettlement->setOrderStatus(SettlementStatus::APPROVED);
        $foundSettlement->setTotalCommission('8.00');
        $repository->save($foundSettlement);

        // 再次清理并查找
        self::getEntityManager()->clear();
        $updatedSettlement = $repository->find($settlementId);
        $this->assertNotNull($updatedSettlement);
        $this->assertSame(SettlementStatus::APPROVED, $updatedSettlement->getOrderStatus());
        $this->assertSame('8', $updatedSettlement->getTotalCommission());

        // 测试删除
        $repository->remove($updatedSettlement);
        $this->assertEntityNotExists(Settlement::class, $settlementId);
    }

    /**
     * 测试根据Publisher查找结算数据
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

        // 为第一个发布商创建结算数据
        $settlement1 = new Settlement();
        $settlement1->setPublisher($publisher1);
        $settlement1->setId(2001);
        $settlement1->setOrderId('ORD-2024-001');
        $settlement1->setWebsiteId(1);
        $settlement1->setTotalPrice('100.00');
        $settlement1->setCampaignName('Campaign 1');
        $settlement1->setBalanceTime('2024-01');
        $settlement1->setOrderTime('2024-01-01 12:00:00');
        $settlement1->setOrderStatus(SettlementStatus::PENDING);
        $settlement1->setTotalCommission('5.00');
        $settlement1->setCurrency(Currency::CNY->value);
        $settlement1->setItemQuantity(1);
        $settlement1->setItemName('Product 1');
        $settlement1->setOriginalCurrency('CNY');
        $settlement1->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($settlement1);

        $settlement2 = new Settlement();
        $settlement2->setPublisher($publisher1);
        $settlement2->setId(2002);
        $settlement2->setOrderId('ORD-2024-002');
        $settlement2->setWebsiteId(1);
        $settlement2->setTotalPrice('200.00');
        $settlement2->setCampaignName('Campaign 2');
        $settlement2->setBalanceTime('2024-02');
        $settlement2->setOrderTime('2024-02-01 12:00:00');
        $settlement2->setOrderStatus(SettlementStatus::APPROVED);
        $settlement2->setTotalCommission('10.00');
        $settlement2->setCurrency(Currency::CNY->value);
        $settlement2->setItemQuantity(2);
        $settlement2->setItemName('Product 2');
        $settlement2->setOriginalCurrency('CNY');
        $settlement2->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($settlement2);

        // 为第二个发布商创建结算数据
        $settlement3 = new Settlement();
        $settlement3->setPublisher($publisher2);
        $settlement3->setId(2003);
        $settlement3->setOrderId('ORD-2024-003');
        $settlement3->setWebsiteId(2);
        $settlement3->setTotalPrice('150.00');
        $settlement3->setCampaignName('Campaign 3');
        $settlement3->setBalanceTime('2024-01');
        $settlement3->setOrderTime('2024-01-15 12:00:00');
        $settlement3->setOrderStatus(SettlementStatus::REJECTED);
        $settlement3->setTotalCommission('7.50');
        $settlement3->setCurrency(Currency::USD->value);
        $settlement3->setItemQuantity(1);
        $settlement3->setItemName('Product 3');
        $settlement3->setOriginalCurrency('USD');
        $settlement3->setOriginalTotalPrice('150.00');
        $this->persistAndFlush($settlement3);

        // 测试查找第一个发布商的结算数据
        $publisher1Settlements = $repository->findByPublisher($publisher1);
        $this->assertCount(2, $publisher1Settlements);

        // 验证按结算时间降序排序
        $this->assertSame(2002, $publisher1Settlements[0]->getId()); // 2024-02
        $this->assertSame(2001, $publisher1Settlements[1]->getId()); // 2024-01

        // 测试查找第二个发布商的结算数据
        $publisher2Settlements = $repository->findByPublisher($publisher2);
        $this->assertCount(1, $publisher2Settlements);
        $this->assertSame(2003, $publisher2Settlements[0]->getId());
    }

    /**
     * 测试根据结算月份查找
     */
    public function testFindByBalanceTime(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建不同月份的结算数据
        $janSettlement = new Settlement();
        $janSettlement->setPublisher($publisher);
        $janSettlement->setId(3001);
        $janSettlement->setOrderId('ORD-2024-001');
        $janSettlement->setWebsiteId(1);
        $janSettlement->setTotalPrice('100.00');
        $janSettlement->setCampaignName('January Campaign');
        $janSettlement->setBalanceTime('2024-01');
        $janSettlement->setOrderTime('2024-01-01 12:00:00');
        $janSettlement->setOrderStatus(SettlementStatus::PENDING);
        $janSettlement->setTotalCommission('5.00');
        $janSettlement->setCurrency(Currency::CNY->value);
        $janSettlement->setItemQuantity(1);
        $janSettlement->setItemName('January Product');
        $janSettlement->setOriginalCurrency('CNY');
        $janSettlement->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($janSettlement);

        $febSettlement = new Settlement();
        $febSettlement->setPublisher($publisher);
        $febSettlement->setId(3002);
        $febSettlement->setOrderId('ORD-2024-002');
        $febSettlement->setWebsiteId(1);
        $febSettlement->setTotalPrice('200.00');
        $febSettlement->setCampaignName('February Campaign');
        $febSettlement->setBalanceTime('2024-02');
        $febSettlement->setOrderTime('2024-02-01 12:00:00');
        $febSettlement->setOrderStatus(SettlementStatus::APPROVED);
        $febSettlement->setTotalCommission('10.00');
        $febSettlement->setCurrency(Currency::CNY->value);
        $febSettlement->setItemQuantity(2);
        $febSettlement->setItemName('February Product');
        $febSettlement->setOriginalCurrency('CNY');
        $febSettlement->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($febSettlement);

        // 测试按月份查找
        $janSettlements = $repository->findByBalanceTime('2024-01');
        $this->assertCount(1, $janSettlements);
        $this->assertSame(3001, $janSettlements[0]->getId());

        $febSettlements = $repository->findByBalanceTime('2024-02');
        $this->assertCount(1, $febSettlements);
        $this->assertSame(3002, $febSettlements[0]->getId());

        // 测试按月份和发布商查找
        $janSettlementsWithPublisher = $repository->findByBalanceTime('2024-01', $publisher);
        $this->assertCount(1, $janSettlementsWithPublisher);
        $this->assertSame(3001, $janSettlementsWithPublisher[0]->getId());

        // 测试查找不存在的月份
        $nonExistentSettlements = $repository->findByBalanceTime('2024-99');
        $this->assertCount(0, $nonExistentSettlements);
    }

    /**
     * 测试根据订单状态查找结算数据
     */
    public function testFindByStatus(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建不同状态的结算数据
        $pendingSettlementId = $this->getUniqueSettlementId();
        $pendingSettlement = new Settlement();
        $pendingSettlement->setPublisher($publisher);
        $pendingSettlement->setId($pendingSettlementId);
        $pendingSettlement->setOrderId('ORD-2024-001');
        $pendingSettlement->setWebsiteId(1);
        $pendingSettlement->setTotalPrice('100.00');
        $pendingSettlement->setCampaignName('Pending Campaign');
        $pendingSettlement->setBalanceTime('2024-01');
        $pendingSettlement->setOrderTime('2024-01-01 12:00:00');
        $pendingSettlement->setOrderStatus(SettlementStatus::PENDING);
        $pendingSettlement->setTotalCommission('5.00');
        $pendingSettlement->setCurrency(Currency::CNY->value);
        $pendingSettlement->setItemQuantity(1);
        $pendingSettlement->setItemName('Pending Product');
        $pendingSettlement->setOriginalCurrency('CNY');
        $pendingSettlement->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($pendingSettlement);

        $approvedSettlementId = $this->getUniqueSettlementId();
        $approvedSettlement = new Settlement();
        $approvedSettlement->setPublisher($publisher);
        $approvedSettlement->setId($approvedSettlementId);
        $approvedSettlement->setOrderId('ORD-2024-002');
        $approvedSettlement->setWebsiteId(1);
        $approvedSettlement->setTotalPrice('200.00');
        $approvedSettlement->setCampaignName('Approved Campaign');
        $approvedSettlement->setBalanceTime('2024-01');
        $approvedSettlement->setOrderTime('2024-01-02 12:00:00');
        $approvedSettlement->setOrderStatus(SettlementStatus::APPROVED);
        $approvedSettlement->setTotalCommission('10.00');
        $approvedSettlement->setCurrency(Currency::CNY->value);
        $approvedSettlement->setItemQuantity(2);
        $approvedSettlement->setItemName('Approved Product');
        $approvedSettlement->setOriginalCurrency('CNY');
        $approvedSettlement->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($approvedSettlement);

        $rejectedSettlementId = $this->getUniqueSettlementId();
        $rejectedSettlement = new Settlement();
        $rejectedSettlement->setPublisher($publisher);
        $rejectedSettlement->setId($rejectedSettlementId);
        $rejectedSettlement->setOrderId('ORD-2024-003');
        $rejectedSettlement->setWebsiteId(1);
        $rejectedSettlement->setTotalPrice('150.00');
        $rejectedSettlement->setCampaignName('Rejected Campaign');
        $rejectedSettlement->setBalanceTime('2024-01');
        $rejectedSettlement->setOrderTime('2024-01-03 12:00:00');
        $rejectedSettlement->setOrderStatus(SettlementStatus::REJECTED);
        $rejectedSettlement->setTotalCommission('7.50');
        $rejectedSettlement->setCurrency(Currency::CNY->value);
        $rejectedSettlement->setItemQuantity(1);
        $rejectedSettlement->setItemName('Rejected Product');
        $rejectedSettlement->setOriginalCurrency('CNY');
        $rejectedSettlement->setOriginalTotalPrice('150.00');
        $this->persistAndFlush($rejectedSettlement);

        // 测试按状态和发布商查找（避免与DataFixtures数据混淆）
        $pendingSettlementsWithPublisher = $repository->findByStatus(SettlementStatus::PENDING->value, $publisher);
        $this->assertCount(1, $pendingSettlementsWithPublisher);
        $this->assertSame($pendingSettlementId, $pendingSettlementsWithPublisher[0]->getId());

        $approvedSettlementsWithPublisher = $repository->findByStatus(SettlementStatus::APPROVED->value, $publisher);
        $this->assertCount(1, $approvedSettlementsWithPublisher);
        $this->assertSame($approvedSettlementId, $approvedSettlementsWithPublisher[0]->getId());

        $rejectedSettlementsWithPublisher = $repository->findByStatus(SettlementStatus::REJECTED->value, $publisher);
        $this->assertCount(1, $rejectedSettlementsWithPublisher);
        $this->assertSame($rejectedSettlementId, $rejectedSettlementsWithPublisher[0]->getId());

        // 测试查找不存在的状态
        $nonExistentSettlements = $repository->findByStatus(999, $publisher);
        $this->assertCount(0, $nonExistentSettlements);
    }

    /**
     * 测试查找待认证的结算数据
     */
    public function testFindPendingSettlements(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建待认证的结算数据，使用唯一ID
        $pendingSettlementId = $this->getUniqueSettlementId();
        $pendingSettlement = new Settlement();
        $pendingSettlement->setPublisher($publisher);
        $pendingSettlement->setId($pendingSettlementId);
        $pendingSettlement->setOrderId('ORD-2024-001');
        $pendingSettlement->setWebsiteId(1);
        $pendingSettlement->setTotalPrice('100.00');
        $pendingSettlement->setCampaignName('Pending Campaign');
        $pendingSettlement->setBalanceTime('2024-01');
        $pendingSettlement->setOrderTime('2024-01-01 12:00:00');
        $pendingSettlement->setOrderStatus(SettlementStatus::PENDING);
        $pendingSettlement->setTotalCommission('5.00');
        $pendingSettlement->setCurrency(Currency::CNY->value);
        $pendingSettlement->setItemQuantity(1);
        $pendingSettlement->setItemName('Pending Product');
        $pendingSettlement->setOriginalCurrency('CNY');
        $pendingSettlement->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($pendingSettlement);

        // 创建已通过的结算数据
        $approvedSettlementId = $this->getUniqueSettlementId();
        $approvedSettlement = new Settlement();
        $approvedSettlement->setPublisher($publisher);
        $approvedSettlement->setId($approvedSettlementId);
        $approvedSettlement->setOrderId('ORD-2024-002');
        $approvedSettlement->setWebsiteId(1);
        $approvedSettlement->setTotalPrice('200.00');
        $approvedSettlement->setCampaignName('Approved Campaign');
        $approvedSettlement->setBalanceTime('2024-01');
        $approvedSettlement->setOrderTime('2024-01-02 12:00:00');
        $approvedSettlement->setOrderStatus(SettlementStatus::APPROVED);
        $approvedSettlement->setTotalCommission('10.00');
        $approvedSettlement->setCurrency(Currency::CNY->value);
        $approvedSettlement->setItemQuantity(2);
        $approvedSettlement->setItemName('Approved Product');
        $approvedSettlement->setOriginalCurrency('CNY');
        $approvedSettlement->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($approvedSettlement);

        // 测试按发布商查找待认证的结算数据（避免DataFixtures数据干扰）
        $pendingSettlementsWithPublisher = $repository->findPendingSettlements($publisher);
        $this->assertCount(1, $pendingSettlementsWithPublisher);
        $this->assertSame($pendingSettlementId, $pendingSettlementsWithPublisher[0]->getId());
        $this->assertSame(SettlementStatus::PENDING, $pendingSettlementsWithPublisher[0]->getOrderStatus());
    }

    /**
     * 测试查找已通过的结算数据
     */
    public function testFindApprovedSettlements(): void
    {
        $repository = $this->getRepository();

        // 清理所有Settlement数据以避免DataFixtures干扰
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\Settlement s')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建待认证的结算数据
        $pendingSettlementId = $this->getUniqueSettlementId();
        $pendingSettlement = new Settlement();
        $pendingSettlement->setPublisher($publisher);
        $pendingSettlement->setId($pendingSettlementId);
        $pendingSettlement->setOrderId('ORD-2024-001');
        $pendingSettlement->setWebsiteId(1);
        $pendingSettlement->setTotalPrice('100.00');
        $pendingSettlement->setCampaignName('Pending Campaign');
        $pendingSettlement->setBalanceTime('2024-01');
        $pendingSettlement->setOrderTime('2024-01-01 12:00:00');
        $pendingSettlement->setOrderStatus(SettlementStatus::PENDING);
        $pendingSettlement->setTotalCommission('5.00');
        $pendingSettlement->setCurrency(Currency::CNY->value);
        $pendingSettlement->setItemQuantity(1);
        $pendingSettlement->setItemName('Pending Product');
        $pendingSettlement->setOriginalCurrency('CNY');
        $pendingSettlement->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($pendingSettlement);

        // 创建已通过的结算数据
        $approvedSettlementId = $this->getUniqueSettlementId();
        $approvedSettlement = new Settlement();
        $approvedSettlement->setPublisher($publisher);
        $approvedSettlement->setId($approvedSettlementId);
        $approvedSettlement->setOrderId('ORD-2024-002');
        $approvedSettlement->setWebsiteId(1);
        $approvedSettlement->setTotalPrice('200.00');
        $approvedSettlement->setCampaignName('Approved Campaign');
        $approvedSettlement->setBalanceTime('2024-01');
        $approvedSettlement->setOrderTime('2024-01-02 12:00:00');
        $approvedSettlement->setOrderStatus(SettlementStatus::APPROVED);
        $approvedSettlement->setTotalCommission('10.00');
        $approvedSettlement->setCurrency(Currency::CNY->value);
        $approvedSettlement->setItemQuantity(2);
        $approvedSettlement->setItemName('Approved Product');
        $approvedSettlement->setOriginalCurrency('CNY');
        $approvedSettlement->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($approvedSettlement);

        // 测试查找已通过的结算数据
        $approvedSettlements = $repository->findApprovedSettlements();
        $this->assertCount(1, $approvedSettlements);
        $this->assertSame($approvedSettlementId, $approvedSettlements[0]->getId());
        $this->assertSame(SettlementStatus::APPROVED, $approvedSettlements[0]->getOrderStatus());

        // 测试按发布商查找已通过的结算数据
        $approvedSettlementsWithPublisher = $repository->findApprovedSettlements($publisher);
        $this->assertCount(1, $approvedSettlementsWithPublisher);
        $this->assertSame($approvedSettlementId, $approvedSettlementsWithPublisher[0]->getId());
    }

    /**
     * 测试查找已拒绝的结算数据
     */
    public function testFindRejectedSettlements(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建待认证的结算数据
        $pendingSettlement = new Settlement();
        $pendingSettlement->setPublisher($publisher);
        $pendingSettlement->setId(7001);
        $pendingSettlement->setOrderId('ORD-2024-001');
        $pendingSettlement->setWebsiteId(1);
        $pendingSettlement->setTotalPrice('100.00');
        $pendingSettlement->setCampaignName('Pending Campaign');
        $pendingSettlement->setBalanceTime('2024-01');
        $pendingSettlement->setOrderTime('2024-01-01 12:00:00');
        $pendingSettlement->setOrderStatus(SettlementStatus::PENDING);
        $pendingSettlement->setTotalCommission('5.00');
        $pendingSettlement->setCurrency(Currency::CNY->value);
        $pendingSettlement->setItemQuantity(1);
        $pendingSettlement->setItemName('Pending Product');
        $pendingSettlement->setOriginalCurrency('CNY');
        $pendingSettlement->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($pendingSettlement);

        // 创建已拒绝的结算数据
        $rejectedSettlement = new Settlement();
        $rejectedSettlement->setPublisher($publisher);
        $rejectedSettlement->setId(7002);
        $rejectedSettlement->setOrderId('ORD-2024-002');
        $rejectedSettlement->setWebsiteId(1);
        $rejectedSettlement->setTotalPrice('200.00');
        $rejectedSettlement->setCampaignName('Rejected Campaign');
        $rejectedSettlement->setBalanceTime('2024-01');
        $rejectedSettlement->setOrderTime('2024-01-02 12:00:00');
        $rejectedSettlement->setOrderStatus(SettlementStatus::REJECTED);
        $rejectedSettlement->setTotalCommission('10.00');
        $rejectedSettlement->setCurrency(Currency::CNY->value);
        $rejectedSettlement->setItemQuantity(2);
        $rejectedSettlement->setItemName('Rejected Product');
        $rejectedSettlement->setOriginalCurrency('CNY');
        $rejectedSettlement->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($rejectedSettlement);

        // 测试按发布商查找已拒绝的结算数据
        $rejectedSettlementsWithPublisher = $repository->findRejectedSettlements($publisher);
        $this->assertCount(1, $rejectedSettlementsWithPublisher);
        $this->assertSame(7002, $rejectedSettlementsWithPublisher[0]->getId());
    }

    /**
     * 测试计算结算总佣金
     */
    public function testCalculateTotalSettlementCommission(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建不同状态的结算数据
        $pendingSettlement = new Settlement();
        $pendingSettlement->setPublisher($publisher);
        $pendingSettlement->setId(8001);
        $pendingSettlement->setOrderId('ORD-2024-001');
        $pendingSettlement->setWebsiteId(1);
        $pendingSettlement->setTotalPrice('100.00');
        $pendingSettlement->setCampaignName('Pending Campaign');
        $pendingSettlement->setBalanceTime('2024-01');
        $pendingSettlement->setOrderTime('2024-01-01 12:00:00');
        $pendingSettlement->setOrderStatus(SettlementStatus::PENDING);
        $pendingSettlement->setTotalCommission('5.00');
        $pendingSettlement->setCurrency(Currency::CNY->value);
        $pendingSettlement->setItemQuantity(1);
        $pendingSettlement->setItemName('Pending Product');
        $pendingSettlement->setOriginalCurrency('CNY');
        $pendingSettlement->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($pendingSettlement);

        $approvedSettlement = new Settlement();
        $approvedSettlement->setPublisher($publisher);
        $approvedSettlement->setId(8002);
        $approvedSettlement->setOrderId('ORD-2024-002');
        $approvedSettlement->setWebsiteId(1);
        $approvedSettlement->setTotalPrice('200.00');
        $approvedSettlement->setCampaignName('Approved Campaign');
        $approvedSettlement->setBalanceTime('2024-01');
        $approvedSettlement->setOrderTime('2024-01-02 12:00:00');
        $approvedSettlement->setOrderStatus(SettlementStatus::APPROVED);
        $approvedSettlement->setTotalCommission('10.00');
        $approvedSettlement->setCurrency(Currency::CNY->value);
        $approvedSettlement->setItemQuantity(2);
        $approvedSettlement->setItemName('Approved Product');
        $approvedSettlement->setOriginalCurrency('CNY');
        $approvedSettlement->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($approvedSettlement);

        $rejectedSettlement = new Settlement();
        $rejectedSettlement->setPublisher($publisher);
        $rejectedSettlement->setId(8003);
        $rejectedSettlement->setOrderId('ORD-2024-003');
        $rejectedSettlement->setWebsiteId(1);
        $rejectedSettlement->setTotalPrice('150.00');
        $rejectedSettlement->setCampaignName('Rejected Campaign');
        $rejectedSettlement->setBalanceTime('2024-02');
        $rejectedSettlement->setOrderTime('2024-02-01 12:00:00');
        $rejectedSettlement->setOrderStatus(SettlementStatus::REJECTED);
        $rejectedSettlement->setTotalCommission('7.50');
        $rejectedSettlement->setCurrency(Currency::USD->value);
        $rejectedSettlement->setItemQuantity(1);
        $rejectedSettlement->setItemName('Rejected Product');
        $rejectedSettlement->setOriginalCurrency('USD');
        $rejectedSettlement->setOriginalTotalPrice('150.00');
        $this->persistAndFlush($rejectedSettlement);

        // 测试按发布商计算所有佣金（避免与DataFixtures数据混淆）
        $totalCommission = $repository->calculateTotalSettlementCommission($publisher);
        $this->assertEquals(22.50, $totalCommission);

        // 测试按发布商计算佣金
        $publisherTotalCommission = $repository->calculateTotalSettlementCommission($publisher);
        $this->assertEquals(22.50, $publisherTotalCommission);

        // 测试按月份计算佣金
        $janTotalCommission = $repository->calculateTotalSettlementCommission($publisher, '2024-01');
        $this->assertEquals(15.00, $janTotalCommission);

        $febTotalCommission = $repository->calculateTotalSettlementCommission($publisher, '2024-02');
        $this->assertEquals(7.50, $febTotalCommission);

        // 测试按状态计算佣金
        $pendingTotalCommission = $repository->calculateTotalSettlementCommission($publisher, null, SettlementStatus::PENDING->value);
        $this->assertEquals(5.00, $pendingTotalCommission);

        $approvedTotalCommission = $repository->calculateTotalSettlementCommission($publisher, null, SettlementStatus::APPROVED->value);
        $this->assertEquals(10.00, $approvedTotalCommission);

        $rejectedTotalCommission = $repository->calculateTotalSettlementCommission($publisher, null, SettlementStatus::REJECTED->value);
        $this->assertEquals(7.50, $rejectedTotalCommission);

        // 测试按月份和状态计算佣金
        $janApprovedTotalCommission = $repository->calculateTotalSettlementCommission($publisher, '2024-01', SettlementStatus::APPROVED->value);
        $this->assertEquals(10.00, $janApprovedTotalCommission);

        // 测试查找不存在的条件
        $nonExistentTotalCommission = $repository->calculateTotalSettlementCommission($publisher, '2024-99');
        $this->assertEquals(0.00, $nonExistentTotalCommission);
    }

    /**
     * 测试获取所有结算月份
     */
    public function testFindAllBalanceMonths(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建不同月份的结算数据
        $janSettlement1 = new Settlement();
        $janSettlement1->setPublisher($publisher);
        $janSettlement1->setId(9001);
        $janSettlement1->setOrderId('ORD-2024-001');
        $janSettlement1->setWebsiteId(1);
        $janSettlement1->setTotalPrice('100.00');
        $janSettlement1->setCampaignName('January Campaign 1');
        $janSettlement1->setBalanceTime('2024-01');
        $janSettlement1->setOrderTime('2024-01-01 12:00:00');
        $janSettlement1->setOrderStatus(SettlementStatus::PENDING);
        $janSettlement1->setTotalCommission('5.00');
        $janSettlement1->setCurrency(Currency::CNY->value);
        $janSettlement1->setItemQuantity(1);
        $janSettlement1->setItemName('January Product 1');
        $janSettlement1->setOriginalCurrency('CNY');
        $janSettlement1->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($janSettlement1);

        $janSettlement2 = new Settlement();
        $janSettlement2->setPublisher($publisher);
        $janSettlement2->setId(9002);
        $janSettlement2->setOrderId('ORD-2024-002');
        $janSettlement2->setWebsiteId(1);
        $janSettlement2->setTotalPrice('200.00');
        $janSettlement2->setCampaignName('January Campaign 2');
        $janSettlement2->setBalanceTime('2024-01');
        $janSettlement2->setOrderTime('2024-01-02 12:00:00');
        $janSettlement2->setOrderStatus(SettlementStatus::APPROVED);
        $janSettlement2->setTotalCommission('10.00');
        $janSettlement2->setCurrency(Currency::CNY->value);
        $janSettlement2->setItemQuantity(2);
        $janSettlement2->setItemName('January Product 2');
        $janSettlement2->setOriginalCurrency('CNY');
        $janSettlement2->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($janSettlement2);

        $febSettlement = new Settlement();
        $febSettlement->setPublisher($publisher);
        $febSettlement->setId(9003);
        $febSettlement->setOrderId('ORD-2024-003');
        $febSettlement->setWebsiteId(1);
        $febSettlement->setTotalPrice('150.00');
        $febSettlement->setCampaignName('February Campaign');
        $febSettlement->setBalanceTime('2024-02');
        $febSettlement->setOrderTime('2024-02-01 12:00:00');
        $febSettlement->setOrderStatus(SettlementStatus::REJECTED);
        $febSettlement->setTotalCommission('7.50');
        $febSettlement->setCurrency(Currency::USD->value);
        $febSettlement->setItemQuantity(1);
        $febSettlement->setItemName('February Product');
        $febSettlement->setOriginalCurrency('USD');
        $febSettlement->setOriginalTotalPrice('150.00');
        $this->persistAndFlush($febSettlement);

        $marSettlement = new Settlement();
        $marSettlement->setPublisher($publisher);
        $marSettlement->setId(9004);
        $marSettlement->setOrderId('ORD-2024-004');
        $marSettlement->setWebsiteId(1);
        $marSettlement->setTotalPrice('300.00');
        $marSettlement->setCampaignName('March Campaign');
        $marSettlement->setBalanceTime('2024-03');
        $marSettlement->setOrderTime('2024-03-01 12:00:00');
        $marSettlement->setOrderStatus(SettlementStatus::PENDING);
        $marSettlement->setTotalCommission('15.00');
        $marSettlement->setCurrency(Currency::EUR->value);
        $marSettlement->setItemQuantity(3);
        $marSettlement->setItemName('March Product');
        $marSettlement->setOriginalCurrency('EUR');
        $marSettlement->setOriginalTotalPrice('300.00');
        $this->persistAndFlush($marSettlement);

        // 测试获取所有结算月份
        $allMonths = $repository->findAllBalanceMonths();
        $this->assertGreaterThanOrEqual(3, count($allMonths)); // 至少包含我们创建的3个月份
        $this->assertContains('2024-01', $allMonths);
        $this->assertContains('2024-02', $allMonths);
        $this->assertContains('2024-03', $allMonths);

        // 验证我们创建的月份存在于结果中（由于可能存在DataFixtures数据，不假设具体位置）
        $createdMonths = array_intersect(['2024-03', '2024-02', '2024-01'], $allMonths);
        $this->assertCount(3, $createdMonths);

        // 测试按发布商获取结算月份
        $publisherMonths = $repository->findAllBalanceMonths($publisher);
        $this->assertCount(3, $publisherMonths);
        $this->assertContains('2024-01', $publisherMonths);
        $this->assertContains('2024-02', $publisherMonths);
        $this->assertContains('2024-03', $publisherMonths);
    }

    /**
     * 测试根据活动ID查找结算数据
     */
    public function testFindByCampaignId(): void
    {
        $repository = $this->getRepository();

        // 清理所有Settlement数据以避免DataFixtures干扰
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\Settlement s')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建带有不同Campaign ID的结算数据
        $settlement1 = new Settlement();
        $settlement1->setPublisher($publisher);
        $settlement1->setId(12001);
        $settlement1->setOrderId('ORD-CAMPAIGN-001');
        $settlement1->setWebsiteId(1);
        $settlement1->setTotalPrice('150.00');
        $settlement1->setCampaignId(1001);
        $settlement1->setCampaignName('Test Campaign 1');
        $settlement1->setBalanceTime('2024-01');
        $settlement1->setOrderTime('2024-01-15 14:30:00');
        $settlement1->setOrderStatus(SettlementStatus::APPROVED);
        $settlement1->setTotalCommission('15.00');
        $settlement1->setCurrency(Currency::CNY->value);
        $settlement1->setItemQuantity(1);
        $settlement1->setItemName('Campaign Product 1');
        $settlement1->setOriginalCurrency('CNY');
        $settlement1->setOriginalTotalPrice('150.00');

        $this->persistAndFlush($settlement1);

        $settlement2 = new Settlement();
        $settlement2->setPublisher($publisher);
        $settlement2->setId(12002);
        $settlement2->setOrderId('ORD-CAMPAIGN-002');
        $settlement2->setWebsiteId(1);
        $settlement2->setTotalPrice('200.00');
        $settlement2->setCampaignId(1002);
        $settlement2->setCampaignName('Test Campaign 2');
        $settlement2->setBalanceTime('2024-01');
        $settlement2->setOrderTime('2024-01-16 10:00:00');
        $settlement2->setOrderStatus(SettlementStatus::PENDING);
        $settlement2->setTotalCommission('20.00');
        $settlement2->setCurrency(Currency::CNY->value);
        $settlement2->setItemQuantity(2);
        $settlement2->setItemName('Campaign Product 2');
        $settlement2->setOriginalCurrency('CNY');
        $settlement2->setOriginalTotalPrice('200.00');

        $this->persistAndFlush($settlement2);

        $settlement3 = new Settlement();
        $settlement3->setPublisher($publisher);
        $settlement3->setId(12003);
        $settlement3->setOrderId('ORD-CAMPAIGN-003');
        $settlement3->setWebsiteId(1);
        $settlement3->setTotalPrice('300.00');
        $settlement3->setCampaignId(1001);
        $settlement3->setCampaignName('Test Campaign 1');
        $settlement3->setBalanceTime('2024-02');
        $settlement3->setOrderTime('2024-02-01 16:45:00');
        $settlement3->setOrderStatus(SettlementStatus::APPROVED);
        $settlement3->setTotalCommission('30.00');
        $settlement3->setCurrency(Currency::USD->value);
        $settlement3->setItemQuantity(1);
        $settlement3->setItemName('Campaign Product 3');
        $settlement3->setOriginalCurrency('USD');
        $settlement3->setOriginalTotalPrice('300.00');

        $this->persistAndFlush($settlement3);

        // 确保所有数据都已持久化并清理EntityManager
        self::getEntityManager()->flush();
        self::getEntityManager()->clear();

        // 验证数据是否正确插入
        $allSettlements = $repository->findAll();
        $this->assertGreaterThanOrEqual(3, count($allSettlements), 'Should have at least 3 settlements');

        // 测试查找Campaign ID为1001的结算数据
        $campaign1Settlements = $repository->findByCampaignId(1001);
        $this->assertCount(2, $campaign1Settlements, 'Should find 2 settlements with campaign ID 1001');

        $campaign1OrderIds = array_map(static fn (Settlement $s) => $s->getOrderId(), $campaign1Settlements);
        $this->assertContains('ORD-CAMPAIGN-001', $campaign1OrderIds);
        $this->assertContains('ORD-CAMPAIGN-003', $campaign1OrderIds);

        // 测试查找Campaign ID为1002的结算数据
        $campaign2Settlements = $repository->findByCampaignId(1002);
        $this->assertCount(1, $campaign2Settlements);
        $this->assertSame('ORD-CAMPAIGN-002', $campaign2Settlements[0]->getOrderId());

        // 测试查找不存在的Campaign ID
        $nonExistentCampaignSettlements = $repository->findByCampaignId(9999);
        $this->assertCount(0, $nonExistentCampaignSettlements);

        // 测试按结算时间排序（最新的在前）
        $this->assertSame('ORD-CAMPAIGN-003', $campaign1Settlements[0]->getOrderId());
        $this->assertSame('ORD-CAMPAIGN-001', $campaign1Settlements[1]->getOrderId());

        // 测试过滤特定Publisher
        $anotherPublisherId = $this->getUniquePublisherId();
        $anotherPublisher = new Publisher();
        $anotherPublisher->setPublisherId($anotherPublisherId);
        $anotherPublisher->setToken("another-token-{$anotherPublisherId}");
        $this->persistAndFlush($anotherPublisher);

        $anotherSettlement = new Settlement();
        $anotherSettlement->setPublisher($anotherPublisher);
        $anotherSettlement->setId(12004);
        $anotherSettlement->setOrderId('ORD-ANOTHER-CAMPAIGN-001');
        $anotherSettlement->setWebsiteId(2);
        $anotherSettlement->setTotalPrice('100.00');
        $anotherSettlement->setCampaignId(1001);
        $anotherSettlement->setCampaignName('Test Campaign 1');
        $anotherSettlement->setBalanceTime('2024-01');
        $anotherSettlement->setOrderTime('2024-01-10 12:00:00');
        $anotherSettlement->setOrderStatus(SettlementStatus::APPROVED);
        $anotherSettlement->setTotalCommission('10.00');
        $anotherSettlement->setCurrency(Currency::CNY->value);
        $anotherSettlement->setItemQuantity(1);
        $anotherSettlement->setItemName('Another Campaign Product');
        $anotherSettlement->setOriginalCurrency('CNY');
        $anotherSettlement->setOriginalTotalPrice('100.00');

        $this->persistAndFlush($anotherSettlement);

        // 测试指定Publisher查找Campaign
        $publisherCampaignSettlements = $repository->findByCampaignId(1001, $publisher);
        $this->assertCount(2, $publisherCampaignSettlements);

        $anotherPublisherCampaignSettlements = $repository->findByCampaignId(1001, $anotherPublisher);
        $this->assertCount(1, $anotherPublisherCampaignSettlements);
        $this->assertSame('ORD-ANOTHER-CAMPAIGN-001', $anotherPublisherCampaignSettlements[0]->getOrderId());
    }

    /**
     * 测试按月统计结算佣金
     */
    public function testGetMonthlyCommissionStats(): void
    {
        $repository = $this->getRepository();

        // 清理所有Settlement数据以避免DataFixtures干扰
        self::getEntityManager()->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\Settlement s')
            ->execute()
        ;
        self::getEntityManager()->clear();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建不同月份的结算数据
        $janSettlement1 = new Settlement();
        $janSettlement1->setPublisher($publisher);
        $janSettlement1->setId(11001);
        $janSettlement1->setOrderId('ORD-2024-001');
        $janSettlement1->setWebsiteId(1);
        $janSettlement1->setTotalPrice('100.00');
        $janSettlement1->setCampaignName('January Campaign 1');
        $janSettlement1->setBalanceTime('2024-01');
        $janSettlement1->setOrderTime('2024-01-01 12:00:00');
        $janSettlement1->setOrderStatus(SettlementStatus::PENDING);
        $janSettlement1->setTotalCommission('5.00');
        $janSettlement1->setCurrency(Currency::CNY->value);
        $janSettlement1->setItemQuantity(1);
        $janSettlement1->setItemName('January Product 1');
        $janSettlement1->setOriginalCurrency('CNY');
        $janSettlement1->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($janSettlement1);

        $janSettlement2 = new Settlement();
        $janSettlement2->setPublisher($publisher);
        $janSettlement2->setId(11002);
        $janSettlement2->setOrderId('ORD-2024-002');
        $janSettlement2->setWebsiteId(1);
        $janSettlement2->setTotalPrice('200.00');
        $janSettlement2->setCampaignName('January Campaign 2');
        $janSettlement2->setBalanceTime('2024-01');
        $janSettlement2->setOrderTime('2024-01-02 12:00:00');
        $janSettlement2->setOrderStatus(SettlementStatus::APPROVED);
        $janSettlement2->setTotalCommission('10.00');
        $janSettlement2->setCurrency(Currency::CNY->value);
        $janSettlement2->setItemQuantity(2);
        $janSettlement2->setItemName('January Product 2');
        $janSettlement2->setOriginalCurrency('CNY');
        $janSettlement2->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($janSettlement2);

        $febSettlement = new Settlement();
        $febSettlement->setPublisher($publisher);
        $febSettlement->setId(11003);
        $febSettlement->setOrderId('ORD-2024-003');
        $febSettlement->setWebsiteId(1);
        $febSettlement->setTotalPrice('150.00');
        $febSettlement->setCampaignName('February Campaign');
        $febSettlement->setBalanceTime('2024-02');
        $febSettlement->setOrderTime('2024-02-01 12:00:00');
        $febSettlement->setOrderStatus(SettlementStatus::REJECTED);
        $febSettlement->setTotalCommission('7.50');
        $febSettlement->setCurrency(Currency::USD->value);
        $febSettlement->setItemQuantity(1);
        $febSettlement->setItemName('February Product');
        $febSettlement->setOriginalCurrency('USD');
        $febSettlement->setOriginalTotalPrice('150.00');
        $this->persistAndFlush($febSettlement);

        // 测试按月统计结算佣金
        $monthlyStats = $repository->getMonthlyCommissionStats();
        $this->assertCount(2, $monthlyStats);

        // 验证按结算时间降序排序
        $this->assertSame('2024-02', $monthlyStats[0]['balanceTime']);
        $this->assertSame('2024-01', $monthlyStats[1]['balanceTime']);

        // 验证统计数据
        $febStats = $monthlyStats[0];
        $this->assertEquals('7.50', $febStats['totalCommission']);
        $this->assertEquals(1, $febStats['transactionCount']);

        $janStats = $monthlyStats[1];
        $this->assertEquals('15.00', $janStats['totalCommission']);
        $this->assertEquals(2, $janStats['transactionCount']);

        // 测试按发布商按月统计结算佣金
        $publisherMonthlyStats = $repository->getMonthlyCommissionStats($publisher);
        $this->assertCount(2, $publisherMonthlyStats);
        $this->assertSame('2024-02', $publisherMonthlyStats[0]['balanceTime']);
        $this->assertSame('2024-01', $publisherMonthlyStats[1]['balanceTime']);
    }

    /**
     * 测试查找或创建结算数据
     */
    public function testFindOrCreate(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 测试创建新结算数据
        $settlement = $repository->findOrCreate(12001, $publisher);
        $this->assertSame(12001, $settlement->getId());
        $this->assertSame($publisher, $settlement->getPublisher());

        // 设置必填字段
        $settlement->setOrderId('ORD-2024-001');
        $settlement->setWebsiteId(1);
        $settlement->setTotalPrice('100.00');
        $settlement->setCampaignName('Test Campaign');
        $settlement->setBalanceTime('2024-01');
        $settlement->setOrderTime('2024-01-01 12:00:00');
        $settlement->setOrderStatus(SettlementStatus::PENDING);
        $settlement->setTotalCommission('5.00');
        $settlement->setCurrency(Currency::CNY->value);
        $settlement->setItemQuantity(1);
        $settlement->setItemName('Test Product');
        $settlement->setOriginalCurrency('CNY');
        $settlement->setOriginalTotalPrice('100.00');

        // 确保结算数据已持久化
        self::getEntityManager()->flush();
        $this->assertEntityPersisted($settlement);

        // 测试查找已存在的结算数据
        $foundSettlement = $repository->findOrCreate(12001, $publisher);
        $this->assertSame(12001, $foundSettlement->getId());
        $foundPublisher = $foundSettlement->getPublisher();
        $this->assertNotNull($foundPublisher, 'Settlement publisher should not be null');
        $this->assertSame($publisher->getPublisherId(), $foundPublisher->getPublisherId());

        // 验证是同一个对象（通过ID比较）
        $this->assertSame($settlement->getId(), $foundSettlement->getId());
    }
}
