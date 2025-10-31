<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\Settlement;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Enum\SettlementStatus;

#[When(env: 'test')]
#[When(env: 'dev')]
class SettlementFixtures extends Fixture implements DependentFixtureInterface
{
    public const SETTLEMENT_1 = 'settlement_1';
    public const SETTLEMENT_2 = 'settlement_2';
    public const SETTLEMENT_3 = 'settlement_3';
    public const SETTLEMENT_4 = 'settlement_4';
    public const SETTLEMENT_5 = 'settlement_5';

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

        // 创建测试结算记录
        $settlement1 = new Settlement();
        $settlement1->setId(6001);
        $settlement1->setPublisher($publisher1);
        $settlement1->setOrderId('JD202311010001');
        $settlement1->setWebsiteId(1);
        $settlement1->setTotalPrice('1299.00');
        $settlement1->setCampaignId(2001);
        $settlement1->setCampaignName('京东商城推广活动');
        $settlement1->setTotalCommission('64.95');
        $settlement1->setOrderTime('2023-11-01 09:30:00');
        $settlement1->setOrderStatus(SettlementStatus::APPROVED);
        $settlement1->setCurrency(Currency::CNY->value);
        $settlement1->setTag('electronics');
        $settlement1->setBalanceTime('2023-11');
        $settlement1->setCategoryId('1001');
        $settlement1->setCategoryName('电子产品');
        $settlement1->setItemQuantity(1);
        $settlement1->setItemName('华为Mate 60 Pro手机');
        $settlement1->setOriginalCurrency(Currency::CNY->value);
        $settlement1->setOriginalTotalPrice('1299.00');
        $settlement1->setCampaign($campaign1);
        $manager->persist($settlement1);
        $this->addReference(self::SETTLEMENT_1, $settlement1);

        $settlement2 = new Settlement();
        $settlement2->setId(6002);
        $settlement2->setPublisher($publisher2);
        $settlement2->setOrderId('TB202310020001');
        $settlement2->setWebsiteId(2);
        $settlement2->setTotalPrice('899.00');
        $settlement2->setCampaignId(2002);
        $settlement2->setCampaignName('淘宝联盟推广');
        $settlement2->setTotalCommission('26.97');
        $settlement2->setOrderTime('2023-10-02 14:20:00');
        $settlement2->setOrderStatus(SettlementStatus::PENDING);
        $settlement2->setCurrency(Currency::CNY->value);
        $settlement2->setTag('clothing');
        $settlement2->setBalanceTime('2023-10');
        $settlement2->setCategoryId('2001');
        $settlement2->setCategoryName('服装');
        $settlement2->setItemQuantity(2);
        $settlement2->setItemName('秋季时尚外套');
        $settlement2->setOriginalCurrency(Currency::CNY->value);
        $settlement2->setOriginalTotalPrice('899.00');
        $settlement2->setCampaign($campaign2);
        $manager->persist($settlement2);
        $this->addReference(self::SETTLEMENT_2, $settlement2);

        $settlement3 = new Settlement();
        $settlement3->setId(6003);
        $settlement3->setPublisher($publisher3);
        $settlement3->setOrderId('AMZ202309030001');
        $settlement3->setWebsiteId(3);
        $settlement3->setTotalPrice('149.99');
        $settlement3->setCampaignId(2003);
        $settlement3->setCampaignName('Amazon Affiliate Program');
        $settlement3->setTotalCommission('14.99');
        $settlement3->setOrderTime('2023-09-03 16:45:00');
        $settlement3->setOrderStatus(SettlementStatus::REJECTED);
        $settlement3->setCurrency(Currency::USD->value);
        $settlement3->setTag('books');
        $settlement3->setBalanceTime('2023-09');
        $settlement3->setCategoryId('3001');
        $settlement3->setCategoryName('图书');
        $settlement3->setItemQuantity(3);
        $settlement3->setItemName('Software Development Books');
        $settlement3->setOriginalCurrency(Currency::USD->value);
        $settlement3->setOriginalTotalPrice('149.99');
        $settlement3->setCampaign($campaign3);
        $manager->persist($settlement3);
        $this->addReference(self::SETTLEMENT_3, $settlement3);

        $settlement4 = new Settlement();
        $settlement4->setId(6004);
        $settlement4->setPublisher($publisher1);
        $settlement4->setOrderId('JD202308040001');
        $settlement4->setWebsiteId(1);
        $settlement4->setTotalPrice('599.00');
        $settlement4->setCampaignId(2001);
        $settlement4->setCampaignName('京东商城推广活动');
        $settlement4->setTotalCommission('29.95');
        $settlement4->setOrderTime('2023-08-04 11:15:00');
        $settlement4->setOrderStatus(SettlementStatus::APPROVED);
        $settlement4->setCurrency(Currency::CNY->value);
        $settlement4->setTag('home');
        $settlement4->setBalanceTime('2023-08');
        $settlement4->setCategoryId('4001');
        $settlement4->setCategoryName('家居用品');
        $settlement4->setItemQuantity(1);
        $settlement4->setItemName('空气净化器');
        $settlement4->setOriginalCurrency(Currency::CNY->value);
        $settlement4->setOriginalTotalPrice('599.00');
        $settlement4->setCampaign($campaign1);
        $manager->persist($settlement4);
        $this->addReference(self::SETTLEMENT_4, $settlement4);

        $settlement5 = new Settlement();
        $settlement5->setId(6005);
        $settlement5->setPublisher($publisher2);
        $settlement5->setOrderId('TB202307050001');
        $settlement5->setWebsiteId(2);
        $settlement5->setTotalPrice('2199.00');
        $settlement5->setCampaignId(2002);
        $settlement5->setCampaignName('淘宝联盟推广');
        $settlement5->setTotalCommission('65.97');
        $settlement5->setOrderTime('2023-07-05 18:30:00');
        $settlement5->setOrderStatus(SettlementStatus::APPROVED);
        $settlement5->setCurrency(Currency::CNY->value);
        $settlement5->setTag('electronics');
        $settlement5->setBalanceTime('2023-07');
        $settlement5->setCategoryId('1001');
        $settlement5->setCategoryName('电子产品');
        $settlement5->setItemQuantity(1);
        $settlement5->setItemName('MacBook Pro 14寸');
        $settlement5->setOriginalCurrency(Currency::CNY->value);
        $settlement5->setOriginalTotalPrice('2199.00');
        $settlement5->setCampaign($campaign2);
        $manager->persist($settlement5);
        $this->addReference(self::SETTLEMENT_5, $settlement5);

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
