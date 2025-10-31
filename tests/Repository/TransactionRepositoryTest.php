<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Repository;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\Transaction;
use Tourze\GaNetBundle\Enum\CampaignApplicationStatus;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Enum\TransactionStatus;
use Tourze\GaNetBundle\Enum\YesNoFlag;
use Tourze\GaNetBundle\Repository\CampaignRepository;
use Tourze\GaNetBundle\Repository\TransactionRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(TransactionRepository::class)]
#[RunTestsInSeparateProcesses]
final class TransactionRepositoryTest extends AbstractRepositoryTestCase
{
    private static int $nextPublisherId = 40000;

    private static int $nextTransactionId = 40000;

    private static int $incrementCounter = 0;

    protected function getRepository(): TransactionRepository
    {
        return self::getService(TransactionRepository::class);
    }

    private function getUniquePublisherId(): int
    {
        return ++self::$nextPublisherId;
    }

    private function getUniqueTransactionId(): int
    {
        return ++self::$nextTransactionId;
    }

    private function getUniqueTimestamp(): int
    {
        return time() + (++self::$incrementCounter);
    }

    /**
     * 获取唯一的大ID，用于避免与DataFixtures冲突
     */
    private function getUniqueTransactionLargeId(): int
    {
        static $largeIdCounter = 50000; // 从50000开始避免与其他测试冲突

        $largeIdCounter = is_numeric($largeIdCounter) ? (int) $largeIdCounter + 1 : 50001;

        return $largeIdCounter;
    }

    protected function createNewEntity(): Transaction
    {
        // 使用唯一时间戳避免 ID 冲突
        $timestamp = $this->getUniqueTimestamp();

        // 创建测试发布商
        $publisher = new Publisher();
        $publisher->setPublisherId($timestamp);
        $publisher->setToken("test-token-{$timestamp}");
        $this->persistAndFlush($publisher);

        // 创建交易但不持久化
        $transaction = new Transaction();
        $transaction->setPublisher($publisher);
        $transaction->setId($timestamp + 1000000); // 确保与Publisher ID不冲突
        $transaction->setOrderId("ORD-TEST-{$timestamp}");
        $transaction->setWebsiteId(1);
        $transaction->setTotalPrice('100.00');
        $transaction->setCampaignName("Test Transaction Campaign {$timestamp}");
        $transaction->setOrderTime('2024-01-01 12:00:00');
        $transaction->setOrderStatus(TransactionStatus::PENDING);
        $transaction->setTotalCommission('5.00');
        $transaction->setCurrency(Currency::CNY);
        $transaction->setItemQuantity(1);
        $transaction->setItemName("Test Transaction Product {$timestamp}");
        $transaction->setOriginalCurrency(Currency::CNY);
        $transaction->setOriginalTotalPrice('100.00');

        return $transaction;
    }

    protected function onSetUp(): void
    {
        // Repository 测试设置方法
        // 清理EntityManager避免identity map冲突
        $em = self::getService(EntityManagerInterface::class);
        $em->clear();

        // 只清理测试过程中创建的数据，保留DataFixtures的数据
        // DataFixtures使用ID 5001-5005，测试数据使用其他范围
        // 删除测试数据：ID在2000-5000范围，以及大于5010的
        $em = self::getService(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\Transaction t WHERE (t.id >= 2000 AND t.id <= 5000) OR t.id > 5010')
            ->execute()
        ;
        // 删除ID大于等于3000的Campaign（测试数据范围）
        $em->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\Campaign c WHERE c.id >= 3000')
            ->execute()
        ;
        // 删除publisher_id大于等于12000的Publisher（包含所有测试数据范围）
        $em->createQuery('DELETE FROM Tourze\GaNetBundle\Entity\Publisher p WHERE p.publisher_id >= 12000')
            ->execute()
        ;
        $em->clear();
    }

    /**
     * 测试基本CRUD操作
     */
    public function testCrudOperations(): void
    {
        $repository = $this->getRepository();

        // 创建测试发布商
        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建测试交易
        $transactionId = $this->getUniqueTransactionId();
        $transaction = new Transaction();
        $transaction->setPublisher($publisher);
        $transaction->setId($transactionId);
        $transaction->setOrderId('ORD-2024-001');
        $transaction->setWebsiteId(1);
        $transaction->setTotalPrice('100.00');
        $transaction->setCampaignName('Test Campaign');
        $transaction->setOrderTime('2024-01-01 12:00:00');
        $transaction->setOrderStatus(TransactionStatus::PENDING);
        $transaction->setTotalCommission('5.00');
        $transaction->setCurrency(Currency::CNY);
        $transaction->setItemQuantity(1);
        $transaction->setItemName('Test Product');
        $transaction->setOriginalCurrency(Currency::CNY);
        $transaction->setOriginalTotalPrice('100.00');

        // 测试保存
        $repository->save($transaction);
        $this->assertEntityPersisted($transaction);

        // 清理EntityManager避免身份冲突
        $em = self::getService(EntityManagerInterface::class);
        $em->clear();

        // 测试查找
        $foundTransaction = $repository->find($transactionId);
        $this->assertNotNull($foundTransaction);
        $this->assertSame('ORD-2024-001', $foundTransaction->getOrderId());
        $this->assertSame(TransactionStatus::PENDING, $foundTransaction->getOrderStatus());

        // 测试更新
        $foundTransaction->setOrderStatus(TransactionStatus::CONFIRMED);
        $foundTransaction->setTotalCommission('8.00');
        $repository->save($foundTransaction);

        $updatedTransaction = $repository->find($transactionId);
        $this->assertNotNull($updatedTransaction);
        $this->assertSame(TransactionStatus::CONFIRMED, $updatedTransaction->getOrderStatus());
        $this->assertSame('8.00', $updatedTransaction->getTotalCommission());

        // 测试删除（使用从数据库查找的实体）
        $repository->remove($updatedTransaction);
        $this->assertEntityNotExists(Transaction::class, $transactionId);
    }

    /**
     * 测试根据Publisher查找交易
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

        // 为第一个发布商创建交易
        $transaction1 = new Transaction();
        $transaction1->setPublisher($publisher1);
        $transaction1->setId(2001);
        $transaction1->setOrderId('ORD-2024-001');
        $transaction1->setWebsiteId(1);
        $transaction1->setTotalPrice('100.00');
        $transaction1->setCampaignName('Campaign 1');
        $transaction1->setOrderTime('2024-01-01 12:00:00');
        $transaction1->setOrderStatus(TransactionStatus::PENDING);
        $transaction1->setTotalCommission('5.00');
        $transaction1->setCurrency(Currency::CNY);
        $transaction1->setItemQuantity(1);
        $transaction1->setItemName('Product 1');
        $transaction1->setOriginalCurrency(Currency::CNY);
        $transaction1->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($transaction1);

        $transaction2 = new Transaction();
        $transaction2->setPublisher($publisher1);
        $transaction2->setId(2002);
        $transaction2->setOrderId('ORD-2024-002');
        $transaction2->setWebsiteId(1);
        $transaction2->setTotalPrice('200.00');
        $transaction2->setCampaignName('Campaign 2');
        $transaction2->setOrderTime('2024-01-02 12:00:00');
        $transaction2->setOrderStatus(TransactionStatus::CONFIRMED);
        $transaction2->setTotalCommission('10.00');
        $transaction2->setCurrency(Currency::CNY);
        $transaction2->setItemQuantity(2);
        $transaction2->setItemName('Product 2');
        $transaction2->setOriginalCurrency(Currency::CNY);
        $transaction2->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($transaction2);

        // 为第二个发布商创建交易
        $transaction3 = new Transaction();
        $transaction3->setPublisher($publisher2);
        $transaction3->setId(2003);
        $transaction3->setOrderId('ORD-2024-003');
        $transaction3->setWebsiteId(2);
        $transaction3->setTotalPrice('150.00');
        $transaction3->setCampaignName('Campaign 3');
        $transaction3->setOrderTime('2024-01-03 12:00:00');
        $transaction3->setOrderStatus(TransactionStatus::REJECTED);
        $transaction3->setTotalCommission('7.50');
        $transaction3->setCurrency(Currency::USD);
        $transaction3->setItemQuantity(1);
        $transaction3->setItemName('Product 3');
        $transaction3->setOriginalCurrency(Currency::USD);
        $transaction3->setOriginalTotalPrice('150.00');
        $this->persistAndFlush($transaction3);

        // 测试查找第一个发布商的交易
        $publisher1Transactions = $repository->findByPublisher($publisher1);
        $this->assertCount(2, $publisher1Transactions);

        // 验证按订单时间降序排序
        $this->assertSame(2002, $publisher1Transactions[0]->getId()); // 2024-01-02
        $this->assertSame(2001, $publisher1Transactions[1]->getId()); // 2024-01-01

        // 测试查找第二个发布商的交易
        $publisher2Transactions = $repository->findByPublisher($publisher2);
        $this->assertCount(1, $publisher2Transactions);
        $this->assertSame(2003, $publisher2Transactions[0]->getId());

        // 测试限制数量
        $limitedTransactions = $repository->findByPublisher($publisher1, 1);
        $this->assertCount(1, $limitedTransactions);
        $this->assertSame(2002, $limitedTransactions[0]->getId());
    }

    /**
     * 测试根据活动查找交易
     */
    public function testFindByCampaign(): void
    {
        $repository = $this->getRepository();

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

        // 为第一个活动创建交易
        $transaction1 = new Transaction();
        $transaction1->setPublisher($publisher);
        $transaction1->setId(4001);
        $transaction1->setOrderId('ORD-2024-001');
        $transaction1->setWebsiteId(1);
        $transaction1->setTotalPrice('100.00');
        $transaction1->setCampaign($campaign1);  // 设置Campaign关联对象
        $transaction1->setCampaignName('Campaign 1');

        $transaction1->setOrderTime('2024-01-01 12:00:00');
        $transaction1->setOrderStatus(TransactionStatus::PENDING);
        $transaction1->setTotalCommission('5.00');
        $transaction1->setCurrency(Currency::CNY);
        $transaction1->setItemQuantity(1);
        $transaction1->setItemName('Campaign 1 Product 1');
        $transaction1->setOriginalCurrency(Currency::CNY);
        $transaction1->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($transaction1);

        $transaction2 = new Transaction();
        $transaction2->setPublisher($publisher);
        $transaction2->setId(4002);
        $transaction2->setOrderId('ORD-2024-002');
        $transaction2->setWebsiteId(1);
        $transaction2->setTotalPrice('200.00');
        $transaction2->setCampaign($campaign1);  // 设置Campaign关联对象
        $transaction2->setCampaignName('Campaign 1');
        $transaction2->setOrderTime('2024-01-02 12:00:00');
        $transaction2->setOrderStatus(TransactionStatus::CONFIRMED);
        $transaction2->setTotalCommission('10.00');
        $transaction2->setCurrency(Currency::CNY);
        $transaction2->setItemQuantity(2);
        $transaction2->setItemName('Campaign 1 Product 2');
        $transaction2->setOriginalCurrency(Currency::CNY);
        $transaction2->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($transaction2);

        // 为第二个活动创建交易
        $transaction3 = new Transaction();
        $transaction3->setPublisher($publisher);
        $transaction3->setId(4003);
        $transaction3->setOrderId('ORD-2024-003');
        $transaction3->setWebsiteId(1);
        $transaction3->setTotalPrice('150.00');
        $transaction3->setCampaign($campaign2);  // 设置Campaign关联对象
        $transaction3->setCampaignName('Campaign 2');
        $transaction3->setOrderTime('2024-01-03 12:00:00');
        $transaction3->setOrderStatus(TransactionStatus::REJECTED);
        $transaction3->setTotalCommission('7.50');
        $transaction3->setCurrency(Currency::USD);
        $transaction3->setItemQuantity(1);
        $transaction3->setItemName('Campaign 2 Product');
        $transaction3->setOriginalCurrency(Currency::USD);
        $transaction3->setOriginalTotalPrice('150.00');
        $this->persistAndFlush($transaction3);

        // 清理并重新连接EntityManager以确保数据已保存
        $em = self::getService(EntityManagerInterface::class);
        $em->flush();

        // 保存campaign1的ID，因为clear()会使对象变为detached状态
        $campaign1Id = $campaign1->getId();
        $em->clear();

        // 重新查找campaign1对象
        $campaign1 = self::getService(CampaignRepository::class)->find($campaign1Id);
        $this->assertNotNull($campaign1, 'Campaign1 should exist after clear');

        // 测试查找第一个活动的交易
        $campaign1Transactions = $repository->findByCampaign($campaign1);
        $this->assertCount(2, $campaign1Transactions);

        // 验证按订单时间降序排序
        $this->assertSame(4002, $campaign1Transactions[0]->getId()); // 2024-01-02
        $this->assertSame(4001, $campaign1Transactions[1]->getId()); // 2024-01-01

        // 测试查找第二个活动的交易
        $campaign2Transactions = $repository->findByCampaign($campaign2);
        $this->assertCount(1, $campaign2Transactions);
        $this->assertSame(4003, $campaign2Transactions[0]->getId());

        // 测试限制数量
        $limitedTransactions = $repository->findByCampaign($campaign1, 1);
        $this->assertCount(1, $limitedTransactions);
        $this->assertSame(4002, $limitedTransactions[0]->getId());
    }

    /**
     * 测试根据订单状态查找交易
     */
    public function testFindByStatus(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建不同状态的交易
        $pendingId = $this->getUniqueTransactionLargeId();
        $pendingTransaction = new Transaction();
        $pendingTransaction->setPublisher($publisher);
        $pendingTransaction->setId($pendingId);
        $pendingTransaction->setOrderId("ORD-2024-{$pendingId}");
        $pendingTransaction->setWebsiteId(1);
        $pendingTransaction->setTotalPrice('100.00');
        $pendingTransaction->setCampaignName('Pending Campaign');
        $pendingTransaction->setOrderTime('2024-01-01 12:00:00');
        $pendingTransaction->setOrderStatus(TransactionStatus::PENDING);
        $pendingTransaction->setTotalCommission('5.00');
        $pendingTransaction->setCurrency(Currency::CNY);
        $pendingTransaction->setItemQuantity(1);
        $pendingTransaction->setItemName('Pending Product');
        $pendingTransaction->setOriginalCurrency(Currency::CNY);
        $pendingTransaction->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($pendingTransaction);

        $confirmedId = $this->getUniqueTransactionLargeId();
        $confirmedTransaction = new Transaction();
        $confirmedTransaction->setPublisher($publisher);
        $confirmedTransaction->setId($confirmedId);
        $confirmedTransaction->setOrderId("ORD-2024-{$confirmedId}");
        $confirmedTransaction->setWebsiteId(1);
        $confirmedTransaction->setTotalPrice('200.00');
        $confirmedTransaction->setCampaignName('Confirmed Campaign');
        $confirmedTransaction->setOrderTime('2024-01-02 12:00:00');
        $confirmedTransaction->setOrderStatus(TransactionStatus::CONFIRMED);
        $confirmedTransaction->setTotalCommission('10.00');
        $confirmedTransaction->setCurrency(Currency::CNY);
        $confirmedTransaction->setItemQuantity(2);
        $confirmedTransaction->setItemName('Confirmed Product');
        $confirmedTransaction->setOriginalCurrency(Currency::CNY);
        $confirmedTransaction->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($confirmedTransaction);

        $rejectedId = $this->getUniqueTransactionLargeId();
        $rejectedTransaction = new Transaction();
        $rejectedTransaction->setPublisher($publisher);
        $rejectedTransaction->setId($rejectedId);
        $rejectedTransaction->setOrderId("ORD-2024-{$rejectedId}");
        $rejectedTransaction->setWebsiteId(1);
        $rejectedTransaction->setTotalPrice('150.00');
        $rejectedTransaction->setCampaignName('Rejected Campaign');
        $rejectedTransaction->setOrderTime('2024-01-03 12:00:00');
        $rejectedTransaction->setOrderStatus(TransactionStatus::REJECTED);
        $rejectedTransaction->setTotalCommission('7.50');
        $rejectedTransaction->setCurrency(Currency::USD);
        $rejectedTransaction->setItemQuantity(1);
        $rejectedTransaction->setItemName('Rejected Product');
        $rejectedTransaction->setOriginalCurrency(Currency::USD);
        $rejectedTransaction->setOriginalTotalPrice('150.00');
        $this->persistAndFlush($rejectedTransaction);

        // 测试按状态和发布商查找（避免与DataFixtures数据冲突）
        $pendingTransactions = $repository->findByStatus(TransactionStatus::PENDING->value, $publisher);
        $this->assertCount(1, $pendingTransactions);
        $this->assertSame($pendingId, $pendingTransactions[0]->getId());

        $confirmedTransactions = $repository->findByStatus(TransactionStatus::CONFIRMED->value, $publisher);
        $this->assertCount(1, $confirmedTransactions);
        $this->assertSame($confirmedId, $confirmedTransactions[0]->getId());

        $rejectedTransactions = $repository->findByStatus(TransactionStatus::REJECTED->value, $publisher);
        $this->assertCount(1, $rejectedTransactions);
        $this->assertSame($rejectedId, $rejectedTransactions[0]->getId());

        // 测试按状态和发布商查找
        $pendingTransactionsWithPublisher = $repository->findByStatus(TransactionStatus::PENDING->value, $publisher);
        $this->assertCount(1, $pendingTransactionsWithPublisher);
        $this->assertSame($pendingId, $pendingTransactionsWithPublisher[0]->getId());

        // 测试查找不存在的状态
        $nonExistentTransactions = $repository->findByStatus(999);
        $this->assertCount(0, $nonExistentTransactions);
    }

    /**
     * 测试查找待认证的交易
     */
    public function testFindPendingTransactions(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建待认证的交易
        $pendingId = $this->getUniqueTransactionLargeId();
        $pendingTransaction = new Transaction();
        $pendingTransaction->setPublisher($publisher);
        $pendingTransaction->setId($pendingId);
        $pendingTransaction->setOrderId("ORD-2024-{$pendingId}");
        $pendingTransaction->setWebsiteId(1);
        $pendingTransaction->setTotalPrice('100.00');
        $pendingTransaction->setCampaignName('Pending Campaign');
        $pendingTransaction->setOrderTime('2024-01-01 12:00:00');
        $pendingTransaction->setOrderStatus(TransactionStatus::PENDING);
        $pendingTransaction->setTotalCommission('5.00');
        $pendingTransaction->setCurrency(Currency::CNY);
        $pendingTransaction->setItemQuantity(1);
        $pendingTransaction->setItemName('Pending Product');
        $pendingTransaction->setOriginalCurrency(Currency::CNY);
        $pendingTransaction->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($pendingTransaction);

        // 创建已认证的交易
        $confirmedId = $this->getUniqueTransactionLargeId();
        $confirmedTransaction = new Transaction();
        $confirmedTransaction->setPublisher($publisher);
        $confirmedTransaction->setId($confirmedId);
        $confirmedTransaction->setOrderId("ORD-2024-{$confirmedId}");
        $confirmedTransaction->setWebsiteId(1);
        $confirmedTransaction->setTotalPrice('200.00');
        $confirmedTransaction->setCampaignName('Confirmed Campaign');
        $confirmedTransaction->setOrderTime('2024-01-02 12:00:00');
        $confirmedTransaction->setOrderStatus(TransactionStatus::CONFIRMED);
        $confirmedTransaction->setTotalCommission('10.00');
        $confirmedTransaction->setCurrency(Currency::CNY);
        $confirmedTransaction->setItemQuantity(2);
        $confirmedTransaction->setItemName('Confirmed Product');
        $confirmedTransaction->setOriginalCurrency(Currency::CNY);
        $confirmedTransaction->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($confirmedTransaction);

        // 测试查找待认证的交易（按发布商）
        $pendingTransactions = $repository->findPendingTransactions($publisher);
        $this->assertCount(1, $pendingTransactions);
        $this->assertSame($pendingId, $pendingTransactions[0]->getId());
        $this->assertSame(TransactionStatus::PENDING, $pendingTransactions[0]->getOrderStatus());

        // 测试按发布商查找待认证的交易
        $pendingTransactionsWithPublisher = $repository->findPendingTransactions($publisher);
        $this->assertCount(1, $pendingTransactionsWithPublisher);
        $this->assertSame($pendingId, $pendingTransactionsWithPublisher[0]->getId());
    }

    /**
     * 测试查找已认证的交易
     */
    public function testFindConfirmedTransactions(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建待认证的交易
        $pendingId = $this->getUniqueTransactionLargeId();
        $pendingTransaction = new Transaction();
        $pendingTransaction->setPublisher($publisher);
        $pendingTransaction->setId($pendingId);
        $pendingTransaction->setOrderId("ORD-2024-{$pendingId}");
        $pendingTransaction->setWebsiteId(1);
        $pendingTransaction->setTotalPrice('100.00');
        $pendingTransaction->setCampaignName('Pending Campaign');
        $pendingTransaction->setOrderTime('2024-01-01 12:00:00');
        $pendingTransaction->setOrderStatus(TransactionStatus::PENDING);
        $pendingTransaction->setTotalCommission('5.00');
        $pendingTransaction->setCurrency(Currency::CNY);
        $pendingTransaction->setItemQuantity(1);
        $pendingTransaction->setItemName('Pending Product');
        $pendingTransaction->setOriginalCurrency(Currency::CNY);
        $pendingTransaction->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($pendingTransaction);

        // 创建已认证的交易
        $confirmedId = $this->getUniqueTransactionLargeId();
        $confirmedTransaction = new Transaction();
        $confirmedTransaction->setPublisher($publisher);
        $confirmedTransaction->setId($confirmedId);
        $confirmedTransaction->setOrderId("ORD-2024-{$confirmedId}");
        $confirmedTransaction->setWebsiteId(1);
        $confirmedTransaction->setTotalPrice('200.00');
        $confirmedTransaction->setCampaignName('Confirmed Campaign');
        $confirmedTransaction->setOrderTime('2024-01-02 12:00:00');
        $confirmedTransaction->setOrderStatus(TransactionStatus::CONFIRMED);
        $confirmedTransaction->setTotalCommission('10.00');
        $confirmedTransaction->setCurrency(Currency::CNY);
        $confirmedTransaction->setItemQuantity(2);
        $confirmedTransaction->setItemName('Confirmed Product');
        $confirmedTransaction->setOriginalCurrency(Currency::CNY);
        $confirmedTransaction->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($confirmedTransaction);

        // 测试按发布商查找已认证的交易（避免与DataFixtures数据冲突）
        $confirmedTransactions = $repository->findConfirmedTransactions($publisher);
        $this->assertCount(1, $confirmedTransactions);
        $this->assertSame($confirmedId, $confirmedTransactions[0]->getId());
        $this->assertSame(TransactionStatus::CONFIRMED, $confirmedTransactions[0]->getOrderStatus());

        // 测试按发布商查找已认证的交易
        $confirmedTransactionsWithPublisher = $repository->findConfirmedTransactions($publisher);
        $this->assertCount(1, $confirmedTransactionsWithPublisher);
        $this->assertSame($confirmedId, $confirmedTransactionsWithPublisher[0]->getId());
    }

    /**
     * 测试查找被拒绝的交易
     */
    public function testFindRejectedTransactions(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建待认证的交易
        $pendingId = $this->getUniqueTransactionLargeId();
        $pendingTransaction = new Transaction();
        $pendingTransaction->setPublisher($publisher);
        $pendingTransaction->setId($pendingId);
        $pendingTransaction->setOrderId("ORD-2024-{$pendingId}");
        $pendingTransaction->setWebsiteId(1);
        $pendingTransaction->setTotalPrice('100.00');
        $pendingTransaction->setCampaignName('Pending Campaign');
        $pendingTransaction->setOrderTime('2024-01-01 12:00:00');
        $pendingTransaction->setOrderStatus(TransactionStatus::PENDING);
        $pendingTransaction->setTotalCommission('5.00');
        $pendingTransaction->setCurrency(Currency::CNY);
        $pendingTransaction->setItemQuantity(1);
        $pendingTransaction->setItemName('Pending Product');
        $pendingTransaction->setOriginalCurrency(Currency::CNY);
        $pendingTransaction->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($pendingTransaction);

        // 创建被拒绝的交易
        $rejectedId = $this->getUniqueTransactionLargeId();
        $rejectedTransaction = new Transaction();
        $rejectedTransaction->setPublisher($publisher);
        $rejectedTransaction->setId($rejectedId);
        $rejectedTransaction->setOrderId("ORD-2024-{$rejectedId}");
        $rejectedTransaction->setWebsiteId(1);
        $rejectedTransaction->setTotalPrice('200.00');
        $rejectedTransaction->setCampaignName('Rejected Campaign');
        $rejectedTransaction->setOrderTime('2024-01-02 12:00:00');
        $rejectedTransaction->setOrderStatus(TransactionStatus::REJECTED);
        $rejectedTransaction->setTotalCommission('10.00');
        $rejectedTransaction->setCurrency(Currency::USD);
        $rejectedTransaction->setItemQuantity(2);
        $rejectedTransaction->setItemName('Rejected Product');
        $rejectedTransaction->setOriginalCurrency(Currency::USD);
        $rejectedTransaction->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($rejectedTransaction);

        // 测试按发布商查找被拒绝的交易（避免与DataFixtures数据冲突）
        $rejectedTransactions = $repository->findRejectedTransactions($publisher);
        $this->assertCount(1, $rejectedTransactions);
        $this->assertSame($rejectedId, $rejectedTransactions[0]->getId());
        $this->assertSame(TransactionStatus::REJECTED, $rejectedTransactions[0]->getOrderStatus());

        // 测试按发布商查找被拒绝的交易
        $rejectedTransactionsWithPublisher = $repository->findRejectedTransactions($publisher);
        $this->assertCount(1, $rejectedTransactionsWithPublisher);
        $this->assertSame($rejectedId, $rejectedTransactionsWithPublisher[0]->getId());
    }

    /**
     * 测试根据日期范围查找交易
     */
    public function testFindByDateRange(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建不同日期的交易
        $janTransaction = new Transaction();
        $janTransaction->setPublisher($publisher);
        $janTransaction->setId(9001);
        $janTransaction->setOrderId('ORD-2024-001');
        $janTransaction->setWebsiteId(1);
        $janTransaction->setTotalPrice('100.00');
        $janTransaction->setCampaignName('January Campaign');
        $janTransaction->setOrderTime('2024-01-15 12:00:00');
        $janTransaction->setOrderStatus(TransactionStatus::PENDING);
        $janTransaction->setTotalCommission('5.00');
        $janTransaction->setCurrency(Currency::CNY);
        $janTransaction->setItemQuantity(1);
        $janTransaction->setItemName('January Product');
        $janTransaction->setOriginalCurrency(Currency::CNY);
        $janTransaction->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($janTransaction);

        $febTransaction = new Transaction();
        $febTransaction->setPublisher($publisher);
        $febTransaction->setId(9002);
        $febTransaction->setOrderId('ORD-2024-002');
        $febTransaction->setWebsiteId(1);
        $febTransaction->setTotalPrice('200.00');
        $febTransaction->setCampaignName('February Campaign');
        $febTransaction->setOrderTime('2024-02-15 12:00:00');
        $febTransaction->setOrderStatus(TransactionStatus::CONFIRMED);
        $febTransaction->setTotalCommission('10.00');
        $febTransaction->setCurrency(Currency::USD);
        $febTransaction->setItemQuantity(2);
        $febTransaction->setItemName('February Product');
        $febTransaction->setOriginalCurrency(Currency::USD);
        $febTransaction->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($febTransaction);

        $marTransaction = new Transaction();
        $marTransaction->setPublisher($publisher);
        $marTransaction->setId(9003);
        $marTransaction->setOrderId('ORD-2024-003');
        $marTransaction->setWebsiteId(1);
        $marTransaction->setTotalPrice('150.00');
        $marTransaction->setCampaignName('March Campaign');
        $marTransaction->setOrderTime('2024-03-15 12:00:00');
        $marTransaction->setOrderStatus(TransactionStatus::REJECTED);
        $marTransaction->setTotalCommission('7.50');
        $marTransaction->setCurrency(Currency::EUR);
        $marTransaction->setItemQuantity(1);
        $marTransaction->setItemName('March Product');
        $marTransaction->setOriginalCurrency(Currency::EUR);
        $marTransaction->setOriginalTotalPrice('150.00');
        $this->persistAndFlush($marTransaction);

        // 测试按日期范围查找
        $startDate = new \DateTime('2024-01-01 00:00:00');
        $endDate = new \DateTime('2024-02-29 23:59:59');

        $janFebTransactions = $repository->findByDateRange($startDate, $endDate);
        $this->assertCount(2, $janFebTransactions);

        // 验证按订单时间降序排序
        $this->assertSame(9002, $janFebTransactions[0]->getId()); // 2024-02-15
        $this->assertSame(9001, $janFebTransactions[1]->getId()); // 2024-01-15

        // 测试按日期范围和发布商查找
        $janFebTransactionsWithPublisher = $repository->findByDateRange($startDate, $endDate, $publisher);
        $this->assertCount(2, $janFebTransactionsWithPublisher);
        $this->assertSame(9002, $janFebTransactionsWithPublisher[0]->getId());
        $this->assertSame(9001, $janFebTransactionsWithPublisher[1]->getId());

        // 测试查找不存在的日期范围
        $futureStartDate = new \DateTime('2025-01-01 00:00:00');
        $futureEndDate = new \DateTime('2025-12-31 23:59:59');
        $futureTransactions = $repository->findByDateRange($futureStartDate, $futureEndDate);
        $this->assertCount(0, $futureTransactions);
    }

    /**
     * 测试计算总佣金
     */
    public function testCalculateTotalCommission(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建不同状态的交易
        $pendingTransaction = new Transaction();
        $pendingTransaction->setPublisher($publisher);
        $pendingTransaction->setId(10001);
        $pendingTransaction->setOrderId('ORD-2024-001');
        $pendingTransaction->setWebsiteId(1);
        $pendingTransaction->setTotalPrice('100.00');
        $pendingTransaction->setCampaignName('Pending Campaign');
        $pendingTransaction->setOrderTime('2024-01-01 12:00:00');
        $pendingTransaction->setOrderStatus(TransactionStatus::PENDING);
        $pendingTransaction->setTotalCommission('5.00');
        $pendingTransaction->setCurrency(Currency::CNY);
        $pendingTransaction->setItemQuantity(1);
        $pendingTransaction->setItemName('Pending Product');
        $pendingTransaction->setOriginalCurrency(Currency::CNY);
        $pendingTransaction->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($pendingTransaction);

        $confirmedTransaction = new Transaction();
        $confirmedTransaction->setPublisher($publisher);
        $confirmedTransaction->setId(10002);
        $confirmedTransaction->setOrderId('ORD-2024-002');
        $confirmedTransaction->setWebsiteId(1);
        $confirmedTransaction->setTotalPrice('200.00');
        $confirmedTransaction->setCampaignName('Confirmed Campaign');
        $confirmedTransaction->setOrderTime('2024-01-02 12:00:00');
        $confirmedTransaction->setOrderStatus(TransactionStatus::CONFIRMED);
        $confirmedTransaction->setTotalCommission('10.00');
        $confirmedTransaction->setCurrency(Currency::CNY);
        $confirmedTransaction->setItemQuantity(2);
        $confirmedTransaction->setItemName('Confirmed Product');
        $confirmedTransaction->setOriginalCurrency(Currency::CNY);
        $confirmedTransaction->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($confirmedTransaction);

        $rejectedTransaction = new Transaction();
        $rejectedTransaction->setPublisher($publisher);
        $rejectedTransaction->setId(10003);
        $rejectedTransaction->setOrderId('ORD-2024-003');
        $rejectedTransaction->setWebsiteId(1);
        $rejectedTransaction->setTotalPrice('150.00');
        $rejectedTransaction->setCampaignName('Rejected Campaign');
        $rejectedTransaction->setOrderTime('2024-01-03 12:00:00');
        $rejectedTransaction->setOrderStatus(TransactionStatus::REJECTED);
        $rejectedTransaction->setTotalCommission('7.50');
        $rejectedTransaction->setCurrency(Currency::USD);
        $rejectedTransaction->setItemQuantity(1);
        $rejectedTransaction->setItemName('Rejected Product');
        $rejectedTransaction->setOriginalCurrency(Currency::USD);
        $rejectedTransaction->setOriginalTotalPrice('150.00');
        $this->persistAndFlush($rejectedTransaction);

        // 测试计算所有佣金（使用发布商过滤避免包含 DataFixtures 数据）
        $totalCommission = $repository->calculateTotalCommission($publisher);
        $this->assertEquals(22.50, $totalCommission);

        // 测试按发布商计算佣金
        $publisherTotalCommission = $repository->calculateTotalCommission($publisher);
        $this->assertEquals(22.50, $publisherTotalCommission);

        // 测试按状态计算佣金
        $pendingTotalCommission = $repository->calculateTotalCommission($publisher, TransactionStatus::PENDING->value);
        $this->assertEquals(5.00, $pendingTotalCommission);

        $confirmedTotalCommission = $repository->calculateTotalCommission($publisher, TransactionStatus::CONFIRMED->value);
        $this->assertEquals(10.00, $confirmedTotalCommission);

        $rejectedTotalCommission = $repository->calculateTotalCommission($publisher, TransactionStatus::REJECTED->value);
        $this->assertEquals(7.50, $rejectedTotalCommission);

        // 测试查找不存在的状态
        $nonExistentTotalCommission = $repository->calculateTotalCommission($publisher, 999);
        $this->assertEquals(0.00, $nonExistentTotalCommission);
    }

    /**
     * 测试查找或创建交易
     */
    public function testFindOrCreate(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 测试创建新交易
        $transaction = $repository->findOrCreate(11001, $publisher);
        $this->assertSame(11001, $transaction->getId());
        $this->assertSame($publisher, $transaction->getPublisher());

        // 设置必填字段
        $transaction->setOrderId('ORD-2024-001');
        $transaction->setWebsiteId(1);
        $transaction->setTotalPrice('100.00');
        $transaction->setCampaignName('Test Campaign');
        $transaction->setOrderTime('2024-01-01 12:00:00');
        $transaction->setOrderStatus(TransactionStatus::PENDING);
        $transaction->setTotalCommission('5.00');
        $transaction->setCurrency(Currency::CNY);
        $transaction->setItemQuantity(1);
        $transaction->setItemName('Test Product');
        $transaction->setOriginalCurrency(Currency::CNY);
        $transaction->setOriginalTotalPrice('100.00');

        // 确保交易已持久化
        $em = self::getService(EntityManagerInterface::class);
        $em->flush();
        $this->assertEntityPersisted($transaction);

        // 测试查找已存在的交易
        $foundTransaction = $repository->findOrCreate(11001, $publisher);
        $this->assertSame(11001, $foundTransaction->getId());
        $foundPublisher = $foundTransaction->getPublisher();
        $this->assertNotNull($foundPublisher, 'Transaction publisher should not be null');
        $this->assertEquals($publisher->getId(), $foundPublisher->getId());

        // 验证是同一个对象（通过ID比较）
        $this->assertSame($transaction->getId(), $foundTransaction->getId());
    }

    /**
     * 测试查找已结算的交易
     */
    public function testFindSettledTransactions(): void
    {
        $repository = $this->getRepository();

        $publisherId = $this->getUniquePublisherId();
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");
        $this->persistAndFlush($publisher);

        // 创建已结算的交易
        $settledTransaction = new Transaction();
        $settledTransaction->setPublisher($publisher);
        $settledTransaction->setId(12001);
        $settledTransaction->setOrderId('ORD-2024-001');
        $settledTransaction->setWebsiteId(1);
        $settledTransaction->setTotalPrice('100.00');
        $settledTransaction->setCampaignName('Settled Campaign');
        $settledTransaction->setOrderTime('2024-01-01 12:00:00');
        $settledTransaction->setOrderStatus(TransactionStatus::CONFIRMED);
        $settledTransaction->setTotalCommission('5.00');
        $settledTransaction->setBalanceTime('2024-01');
        $settledTransaction->setCurrency(Currency::CNY);
        $settledTransaction->setItemQuantity(1);
        $settledTransaction->setItemName('Settled Product');
        $settledTransaction->setOriginalCurrency(Currency::CNY);
        $settledTransaction->setOriginalTotalPrice('100.00');
        $this->persistAndFlush($settledTransaction);

        // 创建未结算的交易
        $unsettledTransaction = new Transaction();
        $unsettledTransaction->setPublisher($publisher);
        $unsettledTransaction->setId(12002);
        $unsettledTransaction->setOrderId('ORD-2024-002');
        $unsettledTransaction->setWebsiteId(1);
        $unsettledTransaction->setTotalPrice('200.00');
        $unsettledTransaction->setCampaignName('Unsettled Campaign');
        $unsettledTransaction->setOrderTime('2024-01-02 12:00:00');
        $unsettledTransaction->setOrderStatus(TransactionStatus::PENDING);
        $unsettledTransaction->setTotalCommission('10.00');
        $unsettledTransaction->setCurrency(Currency::USD);
        $unsettledTransaction->setItemQuantity(2);
        $unsettledTransaction->setItemName('Unsettled Product');
        $unsettledTransaction->setOriginalCurrency(Currency::USD);
        $unsettledTransaction->setOriginalTotalPrice('200.00');
        $this->persistAndFlush($unsettledTransaction);

        // 测试查找已结算的交易（按发布商过滤避免包含 DataFixtures 数据）
        $settledTransactions = $repository->findSettledTransactions($publisher);
        $this->assertCount(1, $settledTransactions);
        $this->assertSame(12001, $settledTransactions[0]->getId());
        $this->assertNotNull($settledTransactions[0]->getBalanceTime());

        // 测试按发布商查找已结算的交易
        $settledTransactionsWithPublisher = $repository->findSettledTransactions($publisher);
        $this->assertCount(1, $settledTransactionsWithPublisher);
        $this->assertSame(12001, $settledTransactionsWithPublisher[0]->getId());
    }
}
