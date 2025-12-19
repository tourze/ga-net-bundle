<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\RedirectTag;
use Tourze\GaNetBundle\Entity\Transaction;
use Tourze\GaNetBundle\Enum\TransactionStatus;
use Tourze\GaNetBundle\Repository\TransactionRepository;
use Tourze\GaNetBundle\Service\RedirectTagService;
use Tourze\GaNetBundle\Service\TransactionTagService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\UserServiceContracts\UserManagerInterface;

/**
 * @internal
 */
#[CoversClass(TransactionTagService::class)]
#[RunTestsInSeparateProcesses]
final class TransactionTagServiceTest extends AbstractIntegrationTestCase
{
    private TransactionTagService $service;
    private RedirectTagService $redirectTagService;
    private TransactionRepository $transactionRepository;
    private UserManagerInterface $userManager;

    protected function onSetUp(): void
    {
        $this->service = self::getService(TransactionTagService::class);
        $this->redirectTagService = self::getService(RedirectTagService::class);
        $this->transactionRepository = self::getService(TransactionRepository::class);
        $this->userManager = self::getService(UserManagerInterface::class);
    }

    #[Test]
    public function testGetUserConversionStatsShouldCalculateCorrectStats(): void
    {
        $userId = 123;
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);

        // 创建测试交易数据
        $transaction1 = $this->createTransaction('100.00', '10.00', TransactionStatus::CONFIRMED);
        $transaction1->setUserId($userId);

        $transaction2 = $this->createTransaction('200.00', '20.00', TransactionStatus::PENDING);
        $transaction2->setUserId($userId);

        $transaction3 = $this->createTransaction('150.00', '15.00', TransactionStatus::REJECTED);
        $transaction3->setUserId($userId);

        self::getEntityManager()->persist($transaction1);
        self::getEntityManager()->persist($transaction2);
        self::getEntityManager()->persist($transaction3);
        self::getEntityManager()->flush();

        $result = $this->service->getUserConversionStats($userId);

        $this->assertSame(3, $result['total_transactions']);
        $this->assertSame('450.00', $result['total_amount']);
        $this->assertSame('45.00', $result['total_commission']);
        $this->assertSame(1, $result['confirmed_transactions']);
        $this->assertSame(1, $result['pending_transactions']);
        $this->assertSame(1, $result['rejected_transactions']);
    }

    #[Test]
    public function testGetUserConversionStatsWithEmptyTransactionsShouldReturnZeros(): void
    {
        $userId = 123;

        $result = $this->service->getUserConversionStats($userId);

        $this->assertSame(0, $result['total_transactions']);
        $this->assertSame('0.00', $result['total_amount']);
        $this->assertSame('0.00', $result['total_commission']);
        $this->assertSame(0, $result['confirmed_transactions']);
        $this->assertSame(0, $result['pending_transactions']);
        $this->assertSame(0, $result['rejected_transactions']);
    }

    #[Test]
    public function testFindTransactionsByUserIdShouldDelegateToRepository(): void
    {
        $userId = 456;
        $limit = 25;

        $result = $this->service->findTransactionsByUserId($userId, $limit);

        $this->assertIsArray($result);
    }

    #[Test]
    public function testGetTagConversionStatsShouldCalculateCorrectStats(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);

        $redirectTag = new RedirectTag();
        $redirectTag->setTag('test-tag-' . uniqid());
        $redirectTag->setPublisher($publisher);
        $redirectTag->setClickTime(new \DateTimeImmutable('2024-01-01 10:00:00'));
        self::getEntityManager()->persist($redirectTag);

        $transaction1 = $this->createTransaction('100.00', '10.00', TransactionStatus::CONFIRMED);
        $transaction1->setRedirectTag($redirectTag);

        $transaction2 = $this->createTransaction('200.00', '20.00', TransactionStatus::CONFIRMED);
        $transaction2->setRedirectTag($redirectTag);

        self::getEntityManager()->persist($transaction1);
        self::getEntityManager()->persist($transaction2);
        self::getEntityManager()->flush();

        $result = $this->service->getTagConversionStats($redirectTag);

        $this->assertEquals($redirectTag->getClickTime(), $result['click_time']);
        $this->assertSame(2, $result['total_transactions']);
        $this->assertSame('300.00', $result['total_amount']);
        $this->assertSame('30.00', $result['total_commission']);
        $this->assertSame('100.00', $result['conversion_rate']); // 有交易即为转化
    }

    #[Test]
    public function testGetTagConversionStatsWithNoTransactionsShouldReturnZeroConversionRate(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);

        $redirectTag = new RedirectTag();
        $redirectTag->setTag('test-tag-' . uniqid());
        $redirectTag->setPublisher($publisher);
        $redirectTag->setClickTime(new \DateTimeImmutable('2024-01-01 10:00:00'));
        self::getEntityManager()->persist($redirectTag);
        self::getEntityManager()->flush();

        $result = $this->service->getTagConversionStats($redirectTag);

        $this->assertSame('0.00', $result['conversion_rate']);
    }

    #[Test]
    public function testFindTransactionsByRedirectTagShouldDelegateToRepository(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);

        $redirectTag = new RedirectTag();
        $redirectTag->setTag('test-tag-' . uniqid());
        $redirectTag->setPublisher($publisher);
        self::getEntityManager()->persist($redirectTag);
        self::getEntityManager()->flush();

        $result = $this->service->findTransactionsByRedirectTag($redirectTag);

        $this->assertIsArray($result);
    }

    #[Test]
    public function testBatchLinkTransactionsWithTagsShouldLinkValidTransactions(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);

        // 创建 RedirectTag
        $redirectTag = new RedirectTag();
        $redirectTag->setTag('tag1');
        $redirectTag->setPublisher($publisher);
        self::getEntityManager()->persist($redirectTag);
        self::getEntityManager()->flush();

        // 创建 Transaction
        $transaction1 = $this->createTransaction('100.00', '10.00', TransactionStatus::CONFIRMED);
        $transaction1->setTag('tag1');

        $transaction2 = $this->createTransaction('200.00', '20.00', TransactionStatus::CONFIRMED);
        $transaction2->setTag(''); // 空标签，应该被跳过

        $transactions = [$transaction1, $transaction2];

        $result = $this->service->batchLinkTransactionsWithTags($transactions);

        $this->assertSame(1, $result); // 只有一个成功链接

        // 验证链接结果
        self::getEntityManager()->refresh($transaction1);
        $this->assertSame($redirectTag, $transaction1->getRedirectTag());
    }

    #[Test]
    public function testLinkTransactionWithTagShouldReturnTrueWhenTagExists(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);

        $redirectTag = new RedirectTag();
        $redirectTag->setTag('existing-tag');
        $redirectTag->setPublisher($publisher);
        self::getEntityManager()->persist($redirectTag);
        self::getEntityManager()->flush();

        $transaction = $this->createTransaction('100.00', '10.00', TransactionStatus::CONFIRMED);
        self::getEntityManager()->persist($transaction);
        self::getEntityManager()->flush();

        $result = $this->service->linkTransactionWithTag($transaction, 'existing-tag');

        $this->assertTrue($result);

        // 验证链接结果
        self::getEntityManager()->refresh($transaction);
        $this->assertSame($redirectTag, $transaction->getRedirectTag());
    }

    #[Test]
    public function testLinkTransactionWithTagShouldSyncUserIdWhenAvailable(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);

        $userId = 789;
        $redirectTag = new RedirectTag();
        $redirectTag->setTag('existing-tag');
        $redirectTag->setPublisher($publisher);
        $redirectTag->setUserId($userId);
        self::getEntityManager()->persist($redirectTag);
        self::getEntityManager()->flush();

        $transaction = $this->createTransaction('100.00', '10.00', TransactionStatus::CONFIRMED);
        self::getEntityManager()->persist($transaction);
        self::getEntityManager()->flush();

        $result = $this->service->linkTransactionWithTag($transaction, 'existing-tag');

        $this->assertTrue($result);

        // 验证链接结果和用户ID同步
        self::getEntityManager()->refresh($transaction);
        $this->assertSame($redirectTag, $transaction->getRedirectTag());
        $this->assertSame($userId, $transaction->getUserId());
    }

    #[Test]
    public function testLinkTransactionWithTagShouldReturnFalseWhenTagNotFound(): void
    {
        $transaction = $this->createTransaction('100.00', '10.00', TransactionStatus::CONFIRMED);
        self::getEntityManager()->persist($transaction);
        self::getEntityManager()->flush();

        $result = $this->service->linkTransactionWithTag($transaction, 'non-existent-tag');

        $this->assertFalse($result);

        // 验证未链接
        self::getEntityManager()->refresh($transaction);
        $this->assertNull($transaction->getRedirectTag());
    }

    #[Test]
    public function testGetConversionRateShouldReturn100WhenTransactionsExist(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);

        $redirectTag = new RedirectTag();
        $redirectTag->setTag('test-tag-' . uniqid());
        $redirectTag->setPublisher($publisher);
        self::getEntityManager()->persist($redirectTag);

        $transaction = $this->createTransaction('100.00', '10.00', TransactionStatus::CONFIRMED);
        $transaction->setRedirectTag($redirectTag);
        self::getEntityManager()->persist($transaction);
        self::getEntityManager()->flush();

        $result = $this->service->getConversionRate($redirectTag);

        $this->assertSame(100.0, $result);
    }

    #[Test]
    public function testGetConversionRateShouldReturn0WhenNoTransactions(): void
    {
        $publisher = new Publisher();
        $publisher->setPublisherId(12345);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);

        $redirectTag = new RedirectTag();
        $redirectTag->setTag('test-tag-' . uniqid());
        $redirectTag->setPublisher($publisher);
        self::getEntityManager()->persist($redirectTag);
        self::getEntityManager()->flush();

        $result = $this->service->getConversionRate($redirectTag);

        $this->assertSame(0.0, $result);
    }

    #[Test]
    public function testGetPublisherConversionStatsShouldReturnEmptyStatsWhenPublisherNotFound(): void
    {
        $publisherId = 999;
        $start = new \DateTimeImmutable('2024-01-01');
        $end = new \DateTimeImmutable('2024-01-31');

        $result = $this->service->getPublisherConversionStats($publisherId, $start, $end);

        $expectedEmptyStats = [
            'click_count' => 0,
            'conversion_count' => 0,
            'conversion_rate' => 0.0,
            'total_amount' => '0.00',
            'total_commission' => '0.00',
        ];

        $this->assertEquals($expectedEmptyStats, $result);
    }

    #[Test]
    public function testGetPublisherConversionStatsShouldCalculateStatsCorrectly(): void
    {
        $publisherId = 123;
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken('test-token');
        self::getEntityManager()->persist($publisher);
        self::getEntityManager()->flush();

        $start = new \DateTimeImmutable('2024-01-01');
        $end = new \DateTimeImmutable('2024-01-31');

        // 创建两个RedirectTag，模拟两个点击
        $redirectTag1 = new RedirectTag();
        $redirectTag1->setTag('tag1-' . uniqid());
        $redirectTag1->setPublisher($publisher);
        $redirectTag1->setClickTime(new \DateTimeImmutable('2024-01-15'));

        $redirectTag2 = new RedirectTag();
        $redirectTag2->setTag('tag2-' . uniqid());
        $redirectTag2->setPublisher($publisher);
        $redirectTag2->setClickTime(new \DateTimeImmutable('2024-01-20'));

        self::getEntityManager()->persist($redirectTag1);
        self::getEntityManager()->persist($redirectTag2);

        // 为第一个标签创建一个交易（有转化）
        $transaction = $this->createTransaction('100.00', '10.00', TransactionStatus::CONFIRMED);
        $transaction->setRedirectTag($redirectTag1);
        self::getEntityManager()->persist($transaction);

        self::getEntityManager()->flush();

        $result = $this->service->getPublisherConversionStats($publisherId, $start, $end);

        $this->assertSame(2, $result['click_count']);
        $this->assertSame(1, $result['conversion_count']);
        $this->assertSame(50.0, $result['conversion_rate']); // 1/2 = 50%
        $this->assertSame('100.00', $result['total_amount']);
        $this->assertSame('10.00', $result['total_commission']);
    }

    private function createTransaction(string $price, string $commission, TransactionStatus $status): Transaction
    {
        $transaction = new Transaction();
        $transaction->setOrderId('order-' . uniqid());
        $transaction->setTotalPrice($price);
        $transaction->setTotalCommission($commission);
        $transaction->setStatus($status);
        $transaction->setOrderTime(new \DateTimeImmutable());

        return $transaction;
    }
}