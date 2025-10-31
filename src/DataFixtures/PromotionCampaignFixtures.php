<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\PromotionCampaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Enum\PromotionType;

#[When(env: 'test')]
#[When(env: 'dev')]
class PromotionCampaignFixtures extends Fixture implements DependentFixtureInterface
{
    public const PROMOTION_1 = 'promotion_1';
    public const PROMOTION_2 = 'promotion_2';
    public const PROMOTION_3 = 'promotion_3';
    public const PROMOTION_4 = 'promotion_4';
    public const PROMOTION_5 = 'promotion_5';

    public function load(ObjectManager $manager): void
    {
        // 从 PublisherFixtures 获取发布商引用
        $publisher1 = $this->getReference(PublisherFixtures::PUBLISHER_1, Publisher::class);
        $publisher2 = $this->getReference(PublisherFixtures::PUBLISHER_2, Publisher::class);
        $publisher3 = $this->getReference(PublisherFixtures::PUBLISHER_3, Publisher::class);

        // 从 CampaignFixtures 获取活动引用
        $campaign1 = $this->getReference(CampaignFixtures::CAMPAIGN_1, Campaign::class);
        $campaign2 = $this->getReference(CampaignFixtures::CAMPAIGN_2, Campaign::class);
        $campaign3 = $this->getReference(CampaignFixtures::CAMPAIGN_3, Campaign::class);

        // 创建折扣类型的促销活动
        $promotion1 = new PromotionCampaign();
        $promotion1->setId(4001);
        $promotion1->setPublisher($publisher1);
        $promotion1->setPromotionType(PromotionType::DISCOUNT);
        $promotion1->setStartTime('2023-12-01 00:00:00');
        $promotion1->setEndTime('2023-12-31 23:59:59');
        $promotion1->setTitle('京东双12大促销');
        $promotion1->setImage('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200"><rect width="400" height="200" fill="%23ccc"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="%23999">Promotion 1</text></svg>');
        $promotion1->setUrl('https://www.jd.com/promotion/1212');
        $promotion1->setDescription('京东双12大促销，全场8折起');
        $promotion1->setCampaignId(2001);
        $promotion1->setMinCommission('5.00');
        $promotion1->setCampaign($campaign1);
        $manager->persist($promotion1);
        $this->addReference(self::PROMOTION_1, $promotion1);

        // 创建优惠券类型的促销活动
        $promotion2 = new PromotionCampaign();
        $promotion2->setId(4002);
        $promotion2->setPublisher($publisher2);
        $promotion2->setPromotionType(PromotionType::COUPON);
        $promotion2->setStartTime('2023-11-01 00:00:00');
        $promotion2->setEndTime('2023-11-30 23:59:59');
        $promotion2->setTitle('淘宝双十一优惠券');
        $promotion2->setImage('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200"><rect width="400" height="200" fill="%23ccc"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="%23999">Promotion 2</text></svg>');
        $promotion2->setUrl('https://www.taobao.com/coupon/1111');
        $promotion2->setDescription('淘宝双十一优惠券，满100减20');
        $promotion2->setCouponCode('TB111120');
        $promotion2->setCampaignId(2002);
        $promotion2->setMinCommission('3.00');
        $promotion2->setCampaign($campaign2);
        $manager->persist($promotion2);
        $this->addReference(self::PROMOTION_2, $promotion2);

        // 创建另一个折扣活动
        $promotion3 = new PromotionCampaign();
        $promotion3->setId(4003);
        $promotion3->setPublisher($publisher3);
        $promotion3->setPromotionType(PromotionType::DISCOUNT);
        $promotion3->setStartTime('2023-10-01 00:00:00');
        $promotion3->setEndTime('2023-10-31 23:59:59');
        $promotion3->setTitle('Amazon Black Friday');
        $promotion3->setImage('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200"><rect width="400" height="200" fill="%23ccc"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="%23999">Promotion 3</text></svg>');
        $promotion3->setUrl('https://www.amazon.com/black-friday');
        $promotion3->setDescription('Amazon Black Friday deals');
        $promotion3->setCampaignId(2003);
        $promotion3->setMinCommission('10.00');
        $promotion3->setCampaign($campaign3);
        $manager->persist($promotion3);
        $this->addReference(self::PROMOTION_3, $promotion3);

        // 创建独立促销活动（不关联特定活动）
        $promotion4 = new PromotionCampaign();
        $promotion4->setId(4004);
        $promotion4->setPublisher($publisher1);
        $promotion4->setPromotionType(PromotionType::COUPON);
        $promotion4->setStartTime('2023-09-01 00:00:00');
        $promotion4->setEndTime('2023-09-30 23:59:59');
        $promotion4->setTitle('京东开学季优惠券');
        $promotion4->setImage('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200"><rect width="400" height="200" fill="%23ccc"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="%23999">Promotion 4</text></svg>');
        $promotion4->setUrl('https://www.jd.com/school');
        $promotion4->setDescription('开学季专享优惠券');
        $promotion4->setCouponCode('JDSCHOOL2023');
        $promotion4->setCampaignId(null);
        $promotion4->setMinCommission('2.00');
        $promotion4->setCampaign(null);
        $manager->persist($promotion4);
        $this->addReference(self::PROMOTION_4, $promotion4);

        // 创建即将过期的促销活动
        $promotion5 = new PromotionCampaign();
        $promotion5->setId(4005);
        $promotion5->setPublisher($publisher2);
        $promotion5->setPromotionType(PromotionType::DISCOUNT);
        $promotion5->setStartTime('2023-08-01 00:00:00');
        $promotion5->setEndTime('2023-08-15 23:59:59');
        $promotion5->setTitle('淘宝夏末清仓');
        $promotion5->setImage('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200"><rect width="400" height="200" fill="%23ccc"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="%23999">Promotion 5</text></svg>');
        $promotion5->setUrl('https://www.taobao.com/summer');
        $promotion5->setDescription('夏末清仓大甩卖');
        $promotion5->setCampaignId(2002);
        $promotion5->setMinCommission('8.00');
        $promotion5->setCampaign($campaign2);
        $manager->persist($promotion5);
        $this->addReference(self::PROMOTION_5, $promotion5);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PublisherFixtures::class,
            CampaignFixtures::class,
        ];
    }
}
