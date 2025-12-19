<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Publisher>
 */
#[AsRepository(entityClass: Publisher::class)]
final class PublisherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publisher::class);
    }

    public function findByPublisherId(int $publisherId): ?Publisher
    {
        return $this->find($publisherId);
    }

    public function findOrCreate(int $publisherId, string $token): Publisher
    {
        $publisher = $this->find($publisherId);

        if (null === $publisher) {
            $publisher = new Publisher();
            $publisher->setPublisherId($publisherId);
            $publisher->setToken($token);
            $this->getEntityManager()->persist($publisher);
            $this->getEntityManager()->flush();
        } else {
            $publisher->setToken($token);
            $this->getEntityManager()->flush();
        }

        return $publisher;
    }

    /**
     * 保存实体
     */
    public function save(Publisher $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(Publisher $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
