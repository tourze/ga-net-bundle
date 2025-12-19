<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\PromotionCampaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Enum\PromotionType;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<PromotionCampaign>
 */
#[AsRepository(entityClass: PromotionCampaign::class)]
final class PromotionCampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PromotionCampaign::class);
    }

    /**
     * 根据Publisher查找促销活动
     * @return array<PromotionCampaign>
     */
    public function findByPublisher(Publisher $publisher): array
    {
        return $this->findBy(['publisher' => $publisher], ['startTime' => 'DESC']);
    }

    /**
     * 根据活动查找促销
     * @return array<PromotionCampaign>
     */
    public function findByCampaign(Campaign $campaign): array
    {
        /** @var array<PromotionCampaign> */
        return $this->createQueryBuilder('p')
            ->where('p.campaign = :campaign')
            ->setParameter('campaign', $campaign)
            ->orderBy('p.startTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据促销类型查找
     * @return array<PromotionCampaign>
     */
    public function findByPromotionType(int $promotionType, ?Publisher $publisher = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.promotionType = :promotionType')
            ->setParameter('promotionType', $promotionType)
            ->orderBy('p.startTime', 'DESC')
        ;

        if (null !== $publisher) {
            $qb->andWhere('p.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        /** @var array<PromotionCampaign> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 查找降价/打折类促销
     * @return array<PromotionCampaign>
     */
    public function findDiscountPromotions(?Publisher $publisher = null): array
    {
        return $this->findByPromotionType(PromotionType::DISCOUNT->value, $publisher);
    }

    /**
     * 查找优惠券类促销
     * @return array<PromotionCampaign>
     */
    public function findCouponPromotions(?Publisher $publisher = null): array
    {
        return $this->findByPromotionType(PromotionType::COUPON->value, $publisher);
    }

    /**
     * 查找当前活跃的促销活动
     * @return array<PromotionCampaign>
     */
    public function findActivePromotions(?Publisher $publisher = null): array
    {
        $now = new \DateTime();
        $nowString = $now->format('Y-m-d H:i:s');

        $qb = $this->createQueryBuilder('p')
            ->where('p.startTime <= :now')
            ->andWhere('p.endTime >= :now')
            ->setParameter('now', $nowString)
            ->orderBy('p.endTime', 'ASC')
        ;

        if (null !== $publisher) {
            $qb->andWhere('p.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        /** @var array<PromotionCampaign> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 查找即将过期的促销活动（7天内）
     * @return array<PromotionCampaign>
     */
    public function findExpiringSoonPromotions(?Publisher $publisher = null): array
    {
        $now = new \DateTime();
        $sevenDaysLater = new \DateTime('+7 days');
        $nowString = $now->format('Y-m-d H:i:s');
        $sevenDaysLaterString = $sevenDaysLater->format('Y-m-d H:i:s');

        $qb = $this->createQueryBuilder('p')
            ->where('p.endTime BETWEEN :now AND :sevenDaysLater')
            ->setParameter('now', $nowString)
            ->setParameter('sevenDaysLater', $sevenDaysLaterString)
            ->orderBy('p.endTime', 'ASC')
        ;

        if (null !== $publisher) {
            $qb->andWhere('p.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        /** @var array<PromotionCampaign> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 根据优惠券码查找促销
     */
    public function findByCouponCode(string $couponCode, ?Publisher $publisher = null): ?PromotionCampaign
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.couponCode = :couponCode')
            ->setParameter('couponCode', $couponCode)
        ;

        if (null !== $publisher) {
            $qb->andWhere('p.publisher = :publisher')
                ->setParameter('publisher', $publisher)
            ;
        }

        /** @var PromotionCampaign|null */
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * 查找或创建促销活动
     */
    public function findOrCreate(int $promotionId, Publisher $publisher): PromotionCampaign
    {
        $promotion = $this->find($promotionId);

        if (null === $promotion) {
            $promotion = new PromotionCampaign();
            $promotion->setPublisher($publisher);
            $promotion->setId($promotionId);
            // 设置必需字段的默认值
            $promotion->setPromotionType(PromotionType::DISCOUNT);
            $promotion->setTitle('Default Promotion');
            $promotion->setStartTime(date('Y-m-d H:i:s'));
            $promotion->setEndTime(date('Y-m-d H:i:s', strtotime('+1 year')));
            $this->getEntityManager()->persist($promotion);
        }

        return $promotion;
    }

    /**
     * 保存实体
     */
    public function save(PromotionCampaign $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(PromotionCampaign $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
