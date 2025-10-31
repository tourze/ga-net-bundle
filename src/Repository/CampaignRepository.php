<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Campaign>
 */
#[AsRepository(entityClass: Campaign::class)]
class CampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campaign::class);
    }

    /**
     * 根据Publisher查找所有活动
     * @return array<Campaign>
     */
    public function findByPublisher(Publisher $publisher): array
    {
        return $this->findBy(['publisher' => $publisher]);
    }

    /**
     * 查找活跃状态的活动
     * @return array<Campaign>
     */
    public function findActiveByPublisher(Publisher $publisher): array
    {
        /** @var array<Campaign> */
        return $this->createQueryBuilder('c')
            ->where('c.publisher = :publisher')
            ->andWhere('c.applicationStatus = :status')
            ->setParameter('publisher', $publisher)
            ->setParameter('status', 5) // 5: 申请通过
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据地区查找活动
     * @return array<Campaign>
     */
    public function findByRegion(string $region, ?Publisher $publisher = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.region = :region')
            ->setParameter('region', $region)
        ;

        if (null !== $publisher) {
            $qb->andWhere('c.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        /** @var array<Campaign> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 根据货币类型查找活动
     * @return array<Campaign>
     */
    public function findByCurrency(string $currency, ?Publisher $publisher = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.currency = :currency')
            ->setParameter('currency', $currency)
        ;

        if (null !== $publisher) {
            $qb->andWhere('c.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        /** @var array<Campaign> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 查找或创建活动
     * 用于从GA Net API同步时，使用外部API提供的ID
     */
    public function findOrCreate(int $campaignId, Publisher $publisher): Campaign
    {
        $campaign = $this->find($campaignId);

        if (null === $campaign) {
            $campaign = new Campaign();
            $campaign->setPublisher($publisher);
            $campaign->setId($campaignId);
            $this->getEntityManager()->persist($campaign);
            $this->getEntityManager()->flush();
        }

        return $campaign;
    }

    /**
     * 保存实体
     */
    public function save(Campaign $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(Campaign $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
