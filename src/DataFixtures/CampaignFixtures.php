<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Enum\CampaignApplicationStatus;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Enum\YesNoFlag;

#[When(env: 'test')]
#[When(env: 'dev')]
class CampaignFixtures extends Fixture implements DependentFixtureInterface
{
    public const CAMPAIGN_1 = 'campaign_1';
    public const CAMPAIGN_2 = 'campaign_2';
    public const CAMPAIGN_3 = 'campaign_3';
    public const CAMPAIGN_4 = 'campaign_4';

    public function load(ObjectManager $manager): void
    {
        // 从 PublisherFixtures 获取发布商引用
        $publisher1 = $this->getReference(PublisherFixtures::PUBLISHER_1, Publisher::class);
        $publisher2 = $this->getReference(PublisherFixtures::PUBLISHER_2, Publisher::class);
        $publisher3 = $this->getReference(PublisherFixtures::PUBLISHER_3, Publisher::class);

        // 创建测试活动
        $campaign1 = new Campaign();
        $campaign1->setPublisher($publisher1);
        $campaign1->setId(2001);
        $campaign1->setRegion('CN');
        $campaign1->setName('京东商城推广活动');
        $campaign1->setTitle('京东商城推广活动标题');
        $campaign1->setUrl('https://www.jd.com');
        $campaign1->setLogo('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect width="200" height="200" fill="%23ccc"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="%23999">Logo 1</text></svg>');
        $campaign1->setStartTime('2023-01-01 00:00:00');
        $campaign1->setCurrency(Currency::CNY);
        $campaign1->setDescription('京东商城推广活动，享受最高10%佣金返利');
        $campaign1->setCookieExpireTime(2592000); // 30天
        $campaign1->setSemPermitted(YesNoFlag::YES);
        $campaign1->setIsLinkCustomizable(YesNoFlag::YES);
        $campaign1->setRebatePermitted(YesNoFlag::YES);
        $campaign1->setHasDatafeed(YesNoFlag::YES);
        $campaign1->setSupportWeapp(YesNoFlag::YES);
        $campaign1->setPromotionalMethods('1,2,4,5,7,8');
        $campaign1->setDataReceptionTime(30);
        $campaign1->setApplicationStatus(CampaignApplicationStatus::APPROVED);
        $manager->persist($campaign1);
        $this->addReference(self::CAMPAIGN_1, $campaign1);

        $campaign2 = new Campaign();
        $campaign2->setPublisher($publisher2);
        $campaign2->setId(2002);
        $campaign2->setRegion('CN');
        $campaign2->setName('淘宝联盟推广');
        $campaign2->setTitle('淘宝联盟推广标题');
        $campaign2->setUrl('https://www.taobao.com');
        $campaign2->setLogo('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect width="200" height="200" fill="%23ccc"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="%23999">Logo 2</text></svg>');
        $campaign2->setStartTime('2023-02-01 00:00:00');
        $campaign2->setCurrency(Currency::CNY);
        $campaign2->setDescription('淘宝联盟推广活动，支持多种推广方式');
        $campaign2->setCookieExpireTime(1296000); // 15天
        $campaign2->setSemPermitted(YesNoFlag::NO);
        $campaign2->setIsLinkCustomizable(YesNoFlag::YES);
        $campaign2->setRebatePermitted(YesNoFlag::YES);
        $campaign2->setHasDatafeed(YesNoFlag::YES);
        $campaign2->setSupportWeapp(YesNoFlag::NO);
        $campaign2->setPromotionalMethods('2,4,5,7,8');
        $campaign2->setDataReceptionTime(15);
        $campaign2->setApplicationStatus(CampaignApplicationStatus::APPLYING);
        $manager->persist($campaign2);
        $this->addReference(self::CAMPAIGN_2, $campaign2);

        $campaign3 = new Campaign();
        $campaign3->setPublisher($publisher3);
        $campaign3->setId(2003);
        $campaign3->setRegion('US');
        $campaign3->setName('Amazon Affiliate Program');
        $campaign3->setTitle('Amazon Affiliate Program Title');
        $campaign3->setUrl('https://www.amazon.com');
        $campaign3->setLogo('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><rect width="200" height="200" fill="%23ccc"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="%23999">Logo 3</text></svg>');
        $campaign3->setStartTime('2023-03-01 00:00:00');
        $campaign3->setCurrency(Currency::USD);
        $campaign3->setDescription('Amazon Affiliate Program with global reach');
        $campaign3->setCookieExpireTime(86400); // 1天
        $campaign3->setSemPermitted(YesNoFlag::YES);
        $campaign3->setIsLinkCustomizable(YesNoFlag::NO);
        $campaign3->setRebatePermitted(YesNoFlag::NO);
        $campaign3->setHasDatafeed(YesNoFlag::NO);
        $campaign3->setSupportWeapp(YesNoFlag::NO);
        $campaign3->setPromotionalMethods('1,2,3,4');
        $campaign3->setDataReceptionTime(60);
        $campaign3->setApplicationStatus(CampaignApplicationStatus::REJECTED);
        $manager->persist($campaign3);
        $this->addReference(self::CAMPAIGN_3, $campaign3);

        $campaign4 = new Campaign();
        $campaign4->setPublisher($publisher1);
        $campaign4->setId(2004);
        $campaign4->setRegion('CN');
        $campaign4->setName('拼多多推广活动');
        $campaign4->setTitle('拼多多推广活动标题');
        $campaign4->setUrl('https://www.pinduoduo.com');
        $campaign4->setStartTime('2023-04-01 00:00:00');
        $campaign4->setCurrency(Currency::CNY);
        $campaign4->setDescription('拼多多社交电商推广活动');
        $campaign4->setCookieExpireTime(172800); // 2天
        $campaign4->setSemPermitted(YesNoFlag::NO);
        $campaign4->setIsLinkCustomizable(YesNoFlag::YES);
        $campaign4->setRebatePermitted(YesNoFlag::YES);
        $campaign4->setHasDatafeed(YesNoFlag::YES);
        $campaign4->setSupportWeapp(YesNoFlag::YES);
        $campaign4->setPromotionalMethods('4,5,7,8');
        $campaign4->setDataReceptionTime(7);
        $campaign4->setApplicationStatus(CampaignApplicationStatus::NOT_APPLIED);
        $manager->persist($campaign4);
        $this->addReference(self::CAMPAIGN_4, $campaign4);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PublisherFixtures::class,
        ];
    }
}
