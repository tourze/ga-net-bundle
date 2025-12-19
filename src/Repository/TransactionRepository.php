<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\Transaction;
use Tourze\GaNetBundle\Enum\TransactionStatus;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
#[AsRepository(entityClass: Transaction::class)]
final class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /**
     * 根据Publisher查找交易
     * @return array<Transaction>
     */
    public function findByPublisher(Publisher $publisher, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.publisher = :publisher')
            ->setParameter('publisher', $publisher)
            ->orderBy('t.orderTime', 'DESC')
        ;

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        /** @var array<Transaction> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 根据活动查找交易
     * @return array<Transaction>
     */
    public function findByCampaign(Campaign $campaign, ?int $limit = null): array
    {
        $campaignId = $campaign->getId();
        if (null === $campaignId) {
            return [];
        }

        $qb = $this->createQueryBuilder('t')
            ->where('t.campaignId = :campaignId')
            ->setParameter('campaignId', $campaignId)
            ->orderBy('t.orderTime', 'DESC')
        ;

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        /** @var array<Transaction> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 根据订单状态查找交易
     * @return array<Transaction>
     */
    public function findByStatus(int $status, ?Publisher $publisher = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.orderStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('t.orderTime', 'DESC')
        ;

        if (null !== $publisher) {
            $qb->andWhere('t.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        /** @var array<Transaction> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 查找待认证的交易
     * @return array<Transaction>
     */
    public function findPendingTransactions(?Publisher $publisher = null): array
    {
        return $this->findByStatus(TransactionStatus::PENDING->value, $publisher);
    }

    /**
     * 查找已认证的交易
     * @return array<Transaction>
     */
    public function findConfirmedTransactions(?Publisher $publisher = null): array
    {
        return $this->findByStatus(TransactionStatus::CONFIRMED->value, $publisher);
    }

    /**
     * 查找被拒绝的交易
     * @return array<Transaction>
     */
    public function findRejectedTransactions(?Publisher $publisher = null): array
    {
        return $this->findByStatus(TransactionStatus::REJECTED->value, $publisher);
    }

    /**
     * 根据日期范围查找交易
     * @return array<Transaction>
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate, ?Publisher $publisher = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.orderTime >= :startDate')
            ->andWhere('t.orderTime <= :endDate')
            ->setParameter('startDate', $startDate->format('Y-m-d H:i:s'))
            ->setParameter('endDate', $endDate->format('Y-m-d H:i:s'))
            ->orderBy('t.orderTime', 'DESC')
        ;

        if (null !== $publisher) {
            $qb->andWhere('t.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        /** @var array<Transaction> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 计算总佣金
     */
    public function calculateTotalCommission(?Publisher $publisher = null, ?int $status = null): float
    {
        $qb = $this->createQueryBuilder('t')
            ->select('SUM(t.totalCommission)')
            ->where('1 = 1')
        ;

        if (null !== $publisher) {
            $qb->andWhere('t.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        if (null !== $status) {
            $qb->andWhere('t.orderStatus = :status')
                ->setParameter('status', $status)
            ;
        }

        return (float) ($qb->getQuery()->getSingleScalarResult() ?? 0);
    }

    /**
     * 查找或创建交易
     */
    public function findOrCreate(int $transactionId, Publisher $publisher): Transaction
    {
        $transaction = $this->find($transactionId);

        if (null === $transaction) {
            $transaction = new Transaction();
            $transaction->setPublisher($publisher);
            $transaction->setId($transactionId);
            $this->getEntityManager()->persist($transaction);
        }

        return $transaction;
    }

    /**
     * 查找已结算的交易
     * @return array<Transaction>
     */
    public function findSettledTransactions(?Publisher $publisher = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.balanceTime IS NOT NULL')
            ->orderBy('t.balanceTime', 'DESC')
        ;

        if (null !== $publisher) {
            $qb->andWhere('t.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        /** @var array<Transaction> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 保存实体
     */
    public function save(Transaction $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(Transaction $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
