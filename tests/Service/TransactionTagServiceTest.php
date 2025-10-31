<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\RedirectTag;
use Tourze\GaNetBundle\Entity\Transaction;
use Tourze\GaNetBundle\Enum\TransactionStatus;
use Tourze\GaNetBundle\Repository\TransactionRepository;
use Tourze\GaNetBundle\Service\RedirectTagService;
use Tourze\GaNetBundle\Service\TransactionTagService;

/**
 * @internal
 */
#[CoversClass(TransactionTagService::class)]
final class TransactionTagServiceTest extends TestCase
{
    /** @var RedirectTagService&MockObject */
    private RedirectTagService $redirectTagService;

    /** @var TransactionRepository&MockObject */
    private TransactionRepository $transactionRepository;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    private TransactionTagService $service;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var RedirectTagService&MockObject $redirectTagService */
        $redirectTagService = $this->createMock(RedirectTagService::class);
        $this->redirectTagService = $redirectTagService;

        /** @var TransactionRepository&MockObject $transactionRepository */
        $transactionRepository = $this->createMock(TransactionRepository::class);
        $this->transactionRepository = $transactionRepository;

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager = $entityManager;

        $this->service = new TransactionTagService(
            $this->redirectTagService,
            $this->transactionRepository,
            $this->entityManager
        );
    }

    #[Test]
    public function testGetUserConversionStatsShouldCalculateCorrectStats(): void
    {
        $userId = 123;
        $transactions = [
            $this->createMockTransaction('100.00', '10.00', TransactionStatus::CONFIRMED),
            $this->createMockTransaction('200.00', '20.00', TransactionStatus::PENDING),
            $this->createMockTransaction('150.00', '15.00', TransactionStatus::REJECTED),
        ];

        $this->transactionRepository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId], ['orderTime' => 'DESC'], 50)
            ->willReturn($transactions)
        ;

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

        $this->transactionRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([])
        ;

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
        /** @var Transaction&MockObject $mockTransaction */
        $mockTransaction = $this->createMock(Transaction::class);
        $expectedTransactions = [$mockTransaction];

        $this->transactionRepository->expects($this->once())
            ->method('findBy')
            ->with(['userId' => $userId], ['orderTime' => 'DESC'], $limit)
            ->willReturn($expectedTransactions)
        ;

        $result = $this->service->findTransactionsByUserId($userId, $limit);

        $this->assertSame($expectedTransactions, $result);
    }

    #[Test]
    public function testGetTagConversionStatsShouldCalculateCorrectStats(): void
    {
        $redirectTag = $this->createMock(RedirectTag::class);
        $clickTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $transactions = [
            $this->createMockTransaction('100.00', '10.00', TransactionStatus::CONFIRMED),
            $this->createMockTransaction('200.00', '20.00', TransactionStatus::CONFIRMED),
        ];

        $redirectTag->method('getClickTime')->willReturn($clickTime);

        $this->transactionRepository->expects($this->once())
            ->method('findBy')
            ->with(['redirectTag' => $redirectTag], ['orderTime' => 'DESC'])
            ->willReturn($transactions)
        ;

        $result = $this->service->getTagConversionStats($redirectTag);

        $this->assertEquals($clickTime, $result['click_time']);
        $this->assertSame(2, $result['total_transactions']);
        $this->assertSame('300.00', $result['total_amount']);
        $this->assertSame('30.00', $result['total_commission']);
        $this->assertSame('100.00', $result['conversion_rate']); // 有交易即为转化
    }

    #[Test]
    public function testGetTagConversionStatsWithNoTransactionsShouldReturnZeroConversionRate(): void
    {
        $redirectTag = $this->createMock(RedirectTag::class);
        $clickTime = new \DateTimeImmutable('2024-01-01 10:00:00');

        $redirectTag->method('getClickTime')->willReturn($clickTime);

        $this->transactionRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([])
        ;

        $result = $this->service->getTagConversionStats($redirectTag);

        $this->assertSame('0.00', $result['conversion_rate']);
    }

    #[Test]
    public function testFindTransactionsByRedirectTagShouldDelegateToRepository(): void
    {
        $redirectTag = $this->createMock(RedirectTag::class);
        /** @var Transaction&MockObject $mockTransaction */
        $mockTransaction = $this->createMock(Transaction::class);
        $expectedTransactions = [$mockTransaction];

        $this->transactionRepository->expects($this->once())
            ->method('findBy')
            ->with(['redirectTag' => $redirectTag], ['orderTime' => 'DESC'])
            ->willReturn($expectedTransactions)
        ;

        $result = $this->service->findTransactionsByRedirectTag($redirectTag);

        $this->assertSame($expectedTransactions, $result);
    }

    #[Test]
    public function testBatchLinkTransactionsWithTagsShouldLinkValidTransactions(): void
    {
        $transaction1 = $this->createMock(Transaction::class);
        $transaction1->method('getTag')->willReturn('tag1');

        $transaction2 = $this->createMock(Transaction::class);
        $transaction2->method('getTag')->willReturn(''); // 空标签，应该被跳过

        $transaction3 = $this->createMock(Transaction::class);
        $transaction3->method('getTag')->willReturn('tag3');

        $transactions = [$transaction1, $transaction2, $transaction3]; // 只包含Transaction对象

        // 模拟RedirectTagService只找到tag1和tag3中的一个
        $this->redirectTagService->expects(self::exactly(2))
            ->method('findActiveByTag')
            ->with(self::logicalOr(self::equalTo('tag1'), self::equalTo('tag3')))
            ->willReturnCallback(function ($tag) {
                return 'tag1' === $tag ? $this->createMock(RedirectTag::class) : null;
            })
        ;

        $transaction1->expects($this->once())->method('setRedirectTag');
        $transaction3->expects($this->never())->method('setRedirectTag');

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->batchLinkTransactionsWithTags($transactions);

        $this->assertSame(1, $result); // 只有一个成功链接
    }

    #[Test]
    public function testLinkTransactionWithTagShouldReturnTrueWhenTagExists(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $tag = 'existing-tag';
        $redirectTag = $this->createMock(RedirectTag::class);

        $this->redirectTagService->expects($this->once())
            ->method('findActiveByTag')
            ->with($tag)
            ->willReturn($redirectTag)
        ;

        $transaction->expects($this->once())
            ->method('setRedirectTag')
            ->with($redirectTag)
        ;

        $redirectTag->method('getUserId')->willReturn(null);

        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->linkTransactionWithTag($transaction, $tag);

        $this->assertTrue($result);
    }

    #[Test]
    public function testLinkTransactionWithTagShouldSyncUserIdWhenAvailable(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $tag = 'existing-tag';
        $redirectTag = $this->createMock(RedirectTag::class);
        $userId = 789;

        $this->redirectTagService->expects($this->once())
            ->method('findActiveByTag')
            ->willReturn($redirectTag)
        ;

        $redirectTag->method('getUserId')->willReturn($userId);

        $transaction->expects($this->once())
            ->method('setRedirectTag')
            ->with($redirectTag)
        ;

        $transaction->expects($this->once())
            ->method('setUserId')
            ->with($userId)
        ;

        $result = $this->service->linkTransactionWithTag($transaction, $tag);

        $this->assertTrue($result);
    }

    #[Test]
    public function testLinkTransactionWithTagShouldReturnFalseWhenTagNotFound(): void
    {
        $transaction = $this->createMock(Transaction::class);
        $tag = 'non-existent-tag';

        $this->redirectTagService->expects($this->once())
            ->method('findActiveByTag')
            ->with($tag)
            ->willReturn(null)
        ;

        $transaction->expects($this->never())->method('setRedirectTag');
        $this->entityManager->expects($this->never())->method('flush');

        $result = $this->service->linkTransactionWithTag($transaction, $tag);

        $this->assertFalse($result);
    }

    #[Test]
    public function testGetConversionRateShouldReturn100WhenTransactionsExist(): void
    {
        $redirectTag = $this->createMock(RedirectTag::class);
        $transactions = [$this->createMock(Transaction::class)];

        $this->transactionRepository->expects($this->once())
            ->method('findBy')
            ->willReturn($transactions)
        ;

        $result = $this->service->getConversionRate($redirectTag);

        $this->assertSame(100.0, $result);
    }

    #[Test]
    public function testGetConversionRateShouldReturn0WhenNoTransactions(): void
    {
        $redirectTag = $this->createMock(RedirectTag::class);

        $this->transactionRepository->expects($this->once())
            ->method('findBy')
            ->willReturn([])
        ;

        $result = $this->service->getConversionRate($redirectTag);

        $this->assertSame(0.0, $result);
    }

    #[Test]
    public function testGetPublisherConversionStatsShouldReturnEmptyStatsWhenPublisherNotFound(): void
    {
        $publisherId = 999;

        // Mock getReference返回null或非Publisher对象
        $this->entityManager->expects($this->once())
            ->method('getReference')
            ->with(Publisher::class, $publisherId)
            ->willReturn(null)
        ;

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
        $start = new \DateTimeImmutable('2024-01-01');
        $end = new \DateTimeImmutable('2024-01-31');

        $this->entityManager->expects($this->once())
            ->method('getReference')
            ->with(Publisher::class, $publisherId)
            ->willReturn($publisher)
        ;

        // 模拟两个点击，一个有转化
        $redirectTag1 = $this->createMock(RedirectTag::class);
        $redirectTag1->method('getClickTime')->willReturn(new \DateTimeImmutable('2024-01-15'));

        $redirectTag2 = $this->createMock(RedirectTag::class);
        $redirectTag2->method('getClickTime')->willReturn(new \DateTimeImmutable('2024-01-20'));

        $this->redirectTagService->expects($this->once())
            ->method('findByPublisher')
            ->with($publisher)
            ->willReturn([$redirectTag1, $redirectTag2])
        ;

        // Track the findBy calls to verify correct parameters
        $findByCalls = [];
        $this->transactionRepository->expects($this->exactly(2))
            ->method('findBy')
            ->willReturnCallback(function ($criteria, $orderBy = null) use (&$findByCalls, $redirectTag1, $redirectTag2) {
                $findByCalls[] = $criteria;

                if (is_array($criteria) && isset($criteria['redirectTag']) && $criteria['redirectTag'] === $redirectTag1) {
                    return [$this->createMockTransaction('100.00', '10.00', TransactionStatus::CONFIRMED)];
                }
                if (is_array($criteria) && isset($criteria['redirectTag']) && $criteria['redirectTag'] === $redirectTag2) {
                    return []; // 第二个没有转化
                }

                return [];
            })
        ;

        $result = $this->service->getPublisherConversionStats($publisherId, $start, $end);

        $this->assertSame(2, $result['click_count']);
        $this->assertSame(1, $result['conversion_count']);
        $this->assertSame(50.0, $result['conversion_rate']); // 1/2 = 50%
        $this->assertSame('100.00', $result['total_amount']);
        $this->assertSame('10.00', $result['total_commission']);
    }

    private function createMockTransaction(string $price, string $commission, TransactionStatus $status): Transaction
    {
        $transaction = $this->createMock(Transaction::class);
        $transaction->method('getTotalPrice')->willReturn($price);
        $transaction->method('getTotalCommission')->willReturn($commission);
        $transaction->method('isConfirmed')->willReturn(TransactionStatus::CONFIRMED === $status);
        $transaction->method('isPending')->willReturn(TransactionStatus::PENDING === $status);
        $transaction->method('isRejected')->willReturn(TransactionStatus::REJECTED === $status);

        return $transaction;
    }
}
