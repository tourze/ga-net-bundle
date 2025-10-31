<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\CommissionRule;
use Tourze\GaNetBundle\Enum\CommissionMode;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<CommissionRule>
 */
#[AsRepository(entityClass: CommissionRule::class)]
class CommissionRuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommissionRule::class);
    }

    /**
     * 根据活动查找佣金规则
     * @return array<CommissionRule>
     */
    public function findByCampaign(Campaign $campaign): array
    {
        return $this->findBy(['campaign' => $campaign], ['startTime' => 'DESC']);
    }

    /**
     * 根据佣金模式查找规则
     * @return array<CommissionRule>
     */
    public function findByMode(int $mode, ?Campaign $campaign = null): array
    {
        $criteria = ['mode' => $mode];
        if (null !== $campaign) {
            $criteria['campaign'] = $campaign;
        }

        return $this->findBy($criteria);
    }

    /**
     * 查找分成模式的佣金规则
     * @return array<CommissionRule>
     */
    public function findPercentageRules(?Campaign $campaign = null): array
    {
        return $this->findByMode(CommissionMode::PERCENTAGE->value, $campaign);
    }

    /**
     * 查找固定佣金模式的规则
     * @return array<CommissionRule>
     */
    public function findFixedRules(?Campaign $campaign = null): array
    {
        return $this->findByMode(CommissionMode::FIXED->value, $campaign);
    }

    /**
     * 根据货币查找佣金规则
     * @return array<CommissionRule>
     */
    public function findByCurrency(string $currency, ?Campaign $campaign = null): array
    {
        $qb = $this->createQueryBuilder('cr')
            ->where('cr.currency = :currency')
            ->setParameter('currency', $currency)
        ;

        if (null !== $campaign) {
            $qb->andWhere('cr.campaign = :campaign')
                ->setParameter('campaign', $campaign)
            ;
        }

        /** @var array<CommissionRule> */
        return $qb->orderBy('cr.startTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找最高佣金比例的规则
     * @return array<CommissionRule>
     */
    public function findHighestRatioRules(Campaign $campaign, int $limit = 10): array
    {
        /** @var array<CommissionRule> */
        return $this->createQueryBuilder('cr')
            ->where('cr.campaign = :campaign')
            ->andWhere('cr.mode = :mode')
            ->setParameter('campaign', $campaign)
            ->setParameter('mode', CommissionMode::PERCENTAGE->value)
            ->orderBy('cr.ratio', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找或创建佣金规则
     */
    public function findOrCreate(int $ruleId, Campaign $campaign): CommissionRule
    {
        $rule = $this->find($ruleId);

        if (null === $rule) {
            $rule = new CommissionRule();
            $rule->setCampaign($campaign);
            $rule->setId($ruleId);
            $this->getEntityManager()->persist($rule);
        }

        return $rule;
    }

    /**
     * 保存实体
     */
    public function save(CommissionRule $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(CommissionRule $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
