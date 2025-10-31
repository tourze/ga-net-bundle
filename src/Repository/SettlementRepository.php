<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\Settlement;
use Tourze\GaNetBundle\Enum\SettlementStatus;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Settlement>
 */
#[AsRepository(entityClass: Settlement::class)]
class SettlementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Settlement::class);
    }

    /**
     * 根据Publisher查找结算数据
     * @return array<Settlement>
     */
    public function findByPublisher(Publisher $publisher): array
    {
        return $this->findBy(['publisher' => $publisher], ['balanceTime' => 'DESC']);
    }

    /**
     * 根据结算月份查找
     * @return array<Settlement>
     */
    public function findByBalanceTime(string $balanceTime, ?Publisher $publisher = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.balanceTime = :balanceTime')
            ->setParameter('balanceTime', $balanceTime)
            ->orderBy('s.orderTime', 'DESC')
        ;

        if (null !== $publisher) {
            $qb->andWhere('s.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        /** @var array<Settlement> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 根据订单状态查找结算数据
     * @return array<Settlement>
     */
    public function findByStatus(int $status, ?Publisher $publisher = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.orderStatus = :status')
            ->setParameter('status', $status)
            ->orderBy('s.balanceTime', 'DESC')
        ;

        if (null !== $publisher) {
            $qb->andWhere('s.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        /** @var array<Settlement> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 查找待认证的结算数据
     * @return array<Settlement>
     */
    public function findPendingSettlements(?Publisher $publisher = null): array
    {
        return $this->findByStatus(SettlementStatus::PENDING->value, $publisher);
    }

    /**
     * 查找已通过的结算数据
     * @return array<Settlement>
     */
    public function findApprovedSettlements(?Publisher $publisher = null): array
    {
        return $this->findByStatus(SettlementStatus::APPROVED->value, $publisher);
    }

    /**
     * 查找已拒绝的结算数据
     * @return array<Settlement>
     */
    public function findRejectedSettlements(?Publisher $publisher = null): array
    {
        return $this->findByStatus(SettlementStatus::REJECTED->value, $publisher);
    }

    /**
     * 计算结算总佣金
     */
    public function calculateTotalSettlementCommission(?Publisher $publisher = null, ?string $balanceTime = null, ?int $status = null): float
    {
        $qb = $this->createQueryBuilder('s')
            ->select('SUM(s.totalCommission)')
            ->where('1 = 1')
        ;

        if (null !== $publisher) {
            $qb->andWhere('s.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        if (null !== $balanceTime) {
            $qb->andWhere('s.balanceTime = :balanceTime')
                ->setParameter('balanceTime', $balanceTime)
            ;
        }

        if (null !== $status) {
            $qb->andWhere('s.orderStatus = :status')
                ->setParameter('status', $status)
            ;
        }

        return (float) ($qb->getQuery()->getSingleScalarResult() ?? 0);
    }

    /**
     * 获取所有结算月份（去重）
     * @return array<string>
     */
    public function findAllBalanceMonths(?Publisher $publisher = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('DISTINCT s.balanceTime')
            ->orderBy('s.balanceTime', 'DESC')
        ;

        if (null !== $publisher) {
            $qb->andWhere('s.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        $result = $qb->getQuery()->getScalarResult();
        $months = array_column($result, 'balanceTime');

        return array_map(fn (mixed $value): string => is_scalar($value) ? (string) $value : '', $months);
    }

    /**
     * 根据活动ID查找结算数据
     * @return array<Settlement>
     */
    public function findByCampaignId(int $campaignId, ?Publisher $publisher = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.campaignId = :campaignId')
            ->setParameter('campaignId', $campaignId)
            ->orderBy('s.balanceTime', 'DESC')
        ;

        if (null !== $publisher) {
            $qb->andWhere('s.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        /** @var array<Settlement> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 查找或创建结算数据
     */
    public function findOrCreate(int $settlementId, Publisher $publisher): Settlement
    {
        $settlement = $this->find($settlementId);

        if (null === $settlement) {
            $settlement = new Settlement();
            $settlement->setPublisher($publisher);
            $settlement->setId($settlementId);
            $this->getEntityManager()->persist($settlement);
        }

        return $settlement;
    }

    /**
     * 按月统计结算佣金
     * @return array<array{balanceTime: string, totalCommission: string, transactionCount: int}>
     */
    public function getMonthlyCommissionStats(?Publisher $publisher = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s.balanceTime, SUM(s.totalCommission) as totalCommission, COUNT(s.id) as transactionCount')
            ->groupBy('s.balanceTime')
            ->orderBy('s.balanceTime', 'DESC')
        ;

        if (null !== $publisher) {
            $qb->andWhere('s.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        /** @var array<array{balanceTime: string, totalCommission: string, transactionCount: int}> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 保存实体
     */
    public function save(Settlement $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(Settlement $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
