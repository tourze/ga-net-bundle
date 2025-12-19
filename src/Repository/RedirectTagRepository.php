<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\RedirectTag;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<RedirectTag>
 */
#[AsRepository(entityClass: RedirectTag::class)]
final class RedirectTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RedirectTag::class);
    }

    public function save(RedirectTag $redirectTag, bool $flush = false): void
    {
        $this->getEntityManager()->persist($redirectTag);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RedirectTag $redirectTag, bool $flush = false): void
    {
        $this->getEntityManager()->remove($redirectTag);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByTag(string $tag): ?RedirectTag
    {
        return $this->findOneBy(['tag' => $tag]);
    }

    public function findActiveByTag(string $tag): ?RedirectTag
    {
        $qb = $this->createQueryBuilder('rt')
            ->where('rt.tag = :tag')
            ->andWhere('rt.expireTime IS NULL OR rt.expireTime > :now')
            ->setParameter('tag', $tag)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1)
        ;

        /** @var RedirectTag|null */
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return array<RedirectTag>
     */
    public function findByUserId(int $userId, int $limit = 50): array
    {
        /** @var array<RedirectTag> */
        return $this->createQueryBuilder('rt')
            ->where('rt.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('rt.clickTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<RedirectTag>
     */
    public function findByPublisher(Publisher $publisher, int $limit = 100): array
    {
        /** @var array<RedirectTag> */
        return $this->createQueryBuilder('rt')
            ->where('rt.publisher = :publisher')
            ->setParameter('publisher', $publisher)
            ->orderBy('rt.clickTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<RedirectTag>
     */
    public function findExpiredTags(?\DateTimeImmutable $before = null): array
    {
        $before ??= new \DateTimeImmutable();

        /** @var array<RedirectTag> */
        return $this->createQueryBuilder('rt')
            ->where('rt.expireTime IS NOT NULL')
            ->andWhere('rt.expireTime <= :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->getResult()
        ;
    }

    public function deleteExpiredTags(?\DateTimeImmutable $before = null): int
    {
        $before ??= new \DateTimeImmutable();

        /** @var int */
        return $this->createQueryBuilder('rt')
            ->delete()
            ->where('rt.expireTime IS NOT NULL')
            ->andWhere('rt.expireTime <= :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute()
        ;
    }

    public function countByDateRange(\DateTimeImmutable $start, \DateTimeImmutable $end): int
    {
        return (int) $this->createQueryBuilder('rt')
            ->select('COUNT(rt.id)')
            ->where('rt.clickTime >= :start')
            ->andWhere('rt.clickTime <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @return list<array{click_count: int, click_date: string}>
     */
    public function getClickStatsByPublisher(Publisher $publisher, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        // 使用原生SQL处理DATE函数，因为DQL不支持DATE()
        $sql = '
            SELECT COUNT(rt.id) as click_count, DATE(rt.click_time) as click_date
            FROM ga_net_redirect_tag rt 
            WHERE rt.publisher_id = :publisher_id 
                AND rt.click_time >= :start 
                AND rt.click_time <= :end
            GROUP BY DATE(rt.click_time)
            ORDER BY DATE(rt.click_time) ASC
        ';

        $connection = $this->getEntityManager()->getConnection();
        $result = $connection->executeQuery($sql, [
            'publisher_id' => $publisher->getPublisherId(),
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        /** @var list<array{click_count: int, click_date: string}> */
        return array_map(static function (array $row): array {
            return [
                'click_count' => is_scalar($row['click_count']) ? (int) $row['click_count'] : 0,
                'click_date' => is_scalar($row['click_date']) ? (string) $row['click_date'] : '',
            ];
        }, $result->fetchAllAssociative());
    }

    /**
     * @return array<array{campaign_id: int|null, campaign_name: string|null, click_count: int}>
     */
    public function getClickStatsByCampaign(Publisher $publisher, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        /** @var array<array{campaign_id: int|null, campaign_name: string|null, click_count: int}> */
        return $this->createQueryBuilder('rt')
            ->select('c.id as campaign_id')
            ->addSelect('c.name as campaign_name')
            ->addSelect('COUNT(rt.id) as click_count')
            ->leftJoin('rt.campaign', 'c')
            ->where('rt.publisher = :publisher')
            ->andWhere('rt.clickTime >= :start')
            ->andWhere('rt.clickTime <= :end')
            ->setParameter('publisher', $publisher)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('c.id', 'c.name')
            ->orderBy('click_count', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }
}
