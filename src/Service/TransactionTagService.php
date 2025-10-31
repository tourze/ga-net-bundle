<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\RedirectTag;
use Tourze\GaNetBundle\Entity\Transaction;
use Tourze\GaNetBundle\Repository\TransactionRepository;

readonly class TransactionTagService
{
    public function __construct(
        private RedirectTagService $redirectTagService,
        private TransactionRepository $transactionRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{total_transactions: int, total_amount: string, total_commission: string, confirmed_transactions: int, pending_transactions: int, rejected_transactions: int}
     */
    public function getUserConversionStats(int $userId): array
    {
        $transactions = $this->findTransactionsByUserId($userId);

        $stats = [
            'total_transactions' => count($transactions),
            'total_amount' => '0.00',
            'total_commission' => '0.00',
            'confirmed_transactions' => 0,
            'pending_transactions' => 0,
            'rejected_transactions' => 0,
        ];

        foreach ($transactions as $transaction) {
            $totalPrice = $this->ensureNumericString($transaction->getTotalPrice());
            $totalCommission = $this->ensureNumericString($transaction->getTotalCommission());
            $stats['total_amount'] = bcadd($stats['total_amount'], $totalPrice, 2);
            $stats['total_commission'] = bcadd($stats['total_commission'], $totalCommission, 2);

            if ($transaction->isConfirmed()) {
                ++$stats['confirmed_transactions'];
            } elseif ($transaction->isPending()) {
                ++$stats['pending_transactions'];
            } elseif ($transaction->isRejected()) {
                ++$stats['rejected_transactions'];
            }
        }

        return $stats;
    }

    /**
     * @return Transaction[]
     */
    public function findTransactionsByUserId(int $userId, int $limit = 50): array
    {
        return $this->transactionRepository->findBy(
            ['userId' => $userId],
            ['orderTime' => 'DESC'],
            $limit
        );
    }

    /**
     * @return array{click_time: \DateTimeImmutable, total_transactions: int, total_amount: string, total_commission: string, conversion_rate: string}
     */
    public function getTagConversionStats(RedirectTag $redirectTag): array
    {
        $transactions = $this->findTransactionsByRedirectTag($redirectTag);

        $clickTime = $redirectTag->getClickTime();
        if (null === $clickTime) {
            throw new \RuntimeException('Click time cannot be null for redirect tag');
        }

        $stats = [
            'click_time' => $clickTime,
            'total_transactions' => count($transactions),
            'total_amount' => '0.00',
            'total_commission' => '0.00',
            'conversion_rate' => '0.00',
        ];

        foreach ($transactions as $transaction) {
            $totalPrice = $this->ensureNumericString($transaction->getTotalPrice());
            $totalCommission = $this->ensureNumericString($transaction->getTotalCommission());
            $stats['total_amount'] = bcadd($stats['total_amount'], $totalPrice, 2);
            $stats['total_commission'] = bcadd($stats['total_commission'], $totalCommission, 2);
        }

        // 计算转化率（简化版，实际应该考虑同一用户多次点击）
        if (count($transactions) > 0) {
            $stats['conversion_rate'] = '100.00'; // 有交易即为转化
        }

        return $stats;
    }

    /**
     * @return Transaction[]
     */
    public function findTransactionsByRedirectTag(RedirectTag $redirectTag): array
    {
        return $this->transactionRepository->findBy(
            ['redirectTag' => $redirectTag],
            ['orderTime' => 'DESC']
        );
    }

    /**
     * @param Transaction[] $transactions
     */
    public function batchLinkTransactionsWithTags(array $transactions): int
    {
        $linkedCount = 0;

        foreach ($transactions as $transaction) {
            $tagValue = $transaction->getTag();
            if (null === $tagValue || '' === $tagValue) {
                continue;
            }

            if ($this->linkTransactionWithTag($transaction, $tagValue)) {
                ++$linkedCount;
            }
        }

        return $linkedCount;
    }

    public function linkTransactionWithTag(Transaction $transaction, string $tag): bool
    {
        $redirectTag = $this->redirectTagService->findActiveByTag($tag);

        if (null === $redirectTag) {
            return false;
        }

        $transaction->setRedirectTag($redirectTag);

        // 如果RedirectTag有用户ID，同步到Transaction
        $userId = $redirectTag->getUserId();
        if (null !== $userId) {
            $transaction->setUserId($userId);
        }

        $this->entityManager->flush();

        return true;
    }

    public function getConversionRate(RedirectTag $redirectTag): float
    {
        $transactions = $this->findTransactionsByRedirectTag($redirectTag);

        if (0 === count($transactions)) {
            return 0.0;
        }

        // 简化的转化率计算：有交易就算转化
        return 100.0;
    }

    /**
     * @return array{click_count: int, conversion_count: int, conversion_rate: float, total_amount: string, total_commission: string}
     */
    public function getPublisherConversionStats(int $publisherId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $publisher = $this->getPublisherReference($publisherId);
        if (null === $publisher) {
            return $this->createEmptyStats();
        }

        $redirectTags = $this->redirectTagService->findByPublisher($publisher);
        $stats = $this->calculateStatsFromRedirectTags($redirectTags, $start, $end);

        return [
            'click_count' => $stats['clickCount'],
            'conversion_count' => $stats['conversionCount'],
            'conversion_rate' => $this->calculateConversionRate($stats['clickCount'], $stats['conversionCount']),
            'total_amount' => $stats['totalAmount'],
            'total_commission' => $stats['totalCommission'],
        ];
    }

    private function getPublisherReference(int $publisherId): ?Publisher
    {
        $publisher = $this->entityManager->getReference('Tourze\GaNetBundle\Entity\Publisher', $publisherId);

        return $publisher instanceof Publisher ? $publisher : null;
    }

    /**
     * @return array{click_count: int, conversion_count: int, conversion_rate: float, total_amount: string, total_commission: string}
     */
    private function createEmptyStats(): array
    {
        return [
            'click_count' => 0,
            'conversion_count' => 0,
            'conversion_rate' => 0.0,
            'total_amount' => '0.00',
            'total_commission' => '0.00',
        ];
    }

    /**
     * @param RedirectTag[] $redirectTags
     * @return array{clickCount: int, conversionCount: int, totalAmount: string, totalCommission: string}
     */
    private function calculateStatsFromRedirectTags(array $redirectTags, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $clickCount = 0;
        $conversionCount = 0;
        $totalAmount = '0.00';
        $totalCommission = '0.00';

        foreach ($redirectTags as $redirectTag) {
            if ($this->isClickInDateRange($redirectTag, $start, $end)) {
                ++$clickCount;

                $amounts = $this->calculateTransactionAmounts($redirectTag);
                if ($amounts['hasTransactions']) {
                    ++$conversionCount;
                    $totalAmount = bcadd($totalAmount, $this->ensureNumericString($amounts['amount']), 2);
                    $totalCommission = bcadd($totalCommission, $this->ensureNumericString($amounts['commission']), 2);
                }
            }
        }

        return [
            'clickCount' => $clickCount,
            'conversionCount' => $conversionCount,
            'totalAmount' => $totalAmount,
            'totalCommission' => $totalCommission,
        ];
    }

    private function isClickInDateRange(RedirectTag $redirectTag, \DateTimeImmutable $start, \DateTimeImmutable $end): bool
    {
        $clickTime = $redirectTag->getClickTime();

        return $clickTime >= $start && $clickTime <= $end;
    }

    /**
     * @return array{hasTransactions: bool, amount: string, commission: string}
     */
    private function calculateTransactionAmounts(RedirectTag $redirectTag): array
    {
        $transactions = $this->findTransactionsByRedirectTag($redirectTag);

        if (0 === count($transactions)) {
            return ['hasTransactions' => false, 'amount' => '0.00', 'commission' => '0.00'];
        }

        $amount = '0.00';
        $commission = '0.00';

        foreach ($transactions as $transaction) {
            if ($transaction->isConfirmed()) {
                $totalPrice = $this->ensureNumericString($transaction->getTotalPrice());
                $totalCommission = $this->ensureNumericString($transaction->getTotalCommission());
                $amount = bcadd($amount, $totalPrice, 2);
                $commission = bcadd($commission, $totalCommission, 2);
            }
        }

        return ['hasTransactions' => true, 'amount' => $amount, 'commission' => $commission];
    }

    private function calculateConversionRate(int $clickCount, int $conversionCount): float
    {
        if ($clickCount <= 0) {
            return 0.0;
        }

        return round(($conversionCount / $clickCount) * 100, 2);
    }

    /**
     * 确保值为数字字符串，用于bcadd函数
     * @param mixed $value
     * @return numeric-string
     */
    private function ensureNumericString(mixed $value): string
    {
        if (is_null($value)) {
            return '0';
        }

        // 确保只对标量类型进行字符串转换
        if (!is_scalar($value)) {
            return '0';
        }

        $stringValue = (string) $value;
        if (!is_numeric($stringValue)) {
            return '0';
        }

        return $stringValue;
    }
}
