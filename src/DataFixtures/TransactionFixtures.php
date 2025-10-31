<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\GaNetBundle\Entity\Campaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\Transaction;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Enum\TransactionStatus;

#[When(env: 'test')]
#[When(env: 'dev')]
class TransactionFixtures extends Fixture implements DependentFixtureInterface
{
    public const TRANSACTION_1 = 'transaction_1';
    public const TRANSACTION_2 = 'transaction_2';
    public const TRANSACTION_3 = 'transaction_3';
    public const TRANSACTION_4 = 'transaction_4';
    public const TRANSACTION_5 = 'transaction_5';

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

        // 创建测试交易记录
        $transaction1 = new Transaction();
        $transaction1->setId(5001);
        $transaction1->setPublisher($publisher1);
        $transaction1->setMemo('京东订单交易');
        $transaction1->setOrderId('JD202312010001');
        $transaction1->setWebsiteId(1);
        $transaction1->setTotalPrice('1999.00');
        $transaction1->setCampaignId(2001);
        $transaction1->setCampaignName('京东商城推广活动');
        $transaction1->setTotalCommission('99.95');
        $transaction1->setOrderTime('2023-12-01 14:30:00');
        $transaction1->setOrderStatus(TransactionStatus::CONFIRMED);
        $transaction1->setCurrency(Currency::CNY);
        $transaction1->setTag('electronics');
        $transaction1->setCategoryId('1001');
        $transaction1->setCategoryName('电子产品');
        $transaction1->setItemQuantity(2);
        $transaction1->setItemName('iPhone 15 Pro 手机');
        $transaction1->setOriginalCurrency(Currency::CNY);
        $transaction1->setOriginalTotalPrice('1999.00');
        $transaction1->setBalanceTime('2023-12-01');
        $transaction1->setCampaign($campaign1);
        $manager->persist($transaction1);
        $this->addReference(self::TRANSACTION_1, $transaction1);

        $transaction2 = new Transaction();
        $transaction2->setId(5002);
        $transaction2->setPublisher($publisher2);
        $transaction2->setMemo('淘宝订单交易');
        $transaction2->setOrderId('TB202311020001');
        $transaction2->setWebsiteId(2);
        $transaction2->setTotalPrice('599.00');
        $transaction2->setCampaignId(2002);
        $transaction2->setCampaignName('淘宝联盟推广');
        $transaction2->setTotalCommission('17.97');
        $transaction2->setOrderTime('2023-11-02 16:45:00');
        $transaction2->setOrderStatus(TransactionStatus::PENDING);
        $transaction2->setCurrency(Currency::CNY);
        $transaction2->setTag('clothing');
        $transaction2->setCategoryId('2001');
        $transaction2->setCategoryName('服装');
        $transaction2->setItemQuantity(3);
        $transaction2->setItemName('冬季羽绒服');
        $transaction2->setOriginalCurrency(Currency::CNY);
        $transaction2->setOriginalTotalPrice('599.00');
        $transaction2->setBalanceTime(null);
        $transaction2->setCampaign($campaign2);
        $manager->persist($transaction2);
        $this->addReference(self::TRANSACTION_2, $transaction2);

        $transaction3 = new Transaction();
        $transaction3->setId(5003);
        $transaction3->setPublisher($publisher3);
        $transaction3->setMemo('Amazon订单交易');
        $transaction3->setOrderId('AMZ202310030001');
        $transaction3->setWebsiteId(3);
        $transaction3->setTotalPrice('299.99');
        $transaction3->setCampaignId(2003);
        $transaction3->setCampaignName('Amazon Affiliate Program');
        $transaction3->setTotalCommission('29.99');
        $transaction3->setOrderTime('2023-10-03 10:15:00');
        $transaction3->setOrderStatus(TransactionStatus::REJECTED);
        $transaction3->setCurrency(Currency::USD);
        $transaction3->setTag('books');
        $transaction3->setCategoryId('3001');
        $transaction3->setCategoryName('图书');
        $transaction3->setItemQuantity(5);
        $transaction3->setItemName('Programming Books Collection');
        $transaction3->setOriginalCurrency(Currency::USD);
        $transaction3->setOriginalTotalPrice('299.99');
        $transaction3->setBalanceTime(null);
        $transaction3->setCampaign($campaign3);
        $manager->persist($transaction3);
        $this->addReference(self::TRANSACTION_3, $transaction3);

        $transaction4 = new Transaction();
        $transaction4->setId(5004);
        $transaction4->setPublisher($publisher1);
        $transaction4->setMemo('京东订单交易');
        $transaction4->setOrderId('JD202312040001');
        $transaction4->setWebsiteId(1);
        $transaction4->setTotalPrice('899.00');
        $transaction4->setCampaignId(2001);
        $transaction4->setCampaignName('京东商城推广活动');
        $transaction4->setTotalCommission('44.95');
        $transaction4->setOrderTime('2023-12-04 20:30:00');
        $transaction4->setOrderStatus(TransactionStatus::CONFIRMED);
        $transaction4->setCurrency(Currency::CNY);
        $transaction4->setTag('home');
        $transaction4->setCategoryId('4001');
        $transaction4->setCategoryName('家居用品');
        $transaction4->setItemQuantity(1);
        $transaction4->setItemName('智能扫地机器人');
        $transaction4->setOriginalCurrency(Currency::CNY);
        $transaction4->setOriginalTotalPrice('899.00');
        $transaction4->setBalanceTime('2023-12-04');
        $transaction4->setCampaign($campaign1);
        $manager->persist($transaction4);
        $this->addReference(self::TRANSACTION_4, $transaction4);

        $transaction5 = new Transaction();
        $transaction5->setId(5005);
        $transaction5->setPublisher($publisher2);
        $transaction5->setMemo('淘宝订单交易');
        $transaction5->setOrderId('TB202311050001');
        $transaction5->setWebsiteId(2);
        $transaction5->setTotalPrice('1299.00');
        $transaction5->setCampaignId(2002);
        $transaction5->setCampaignName('淘宝联盟推广');
        $transaction5->setTotalCommission('38.97');
        $transaction5->setOrderTime('2023-11-05 12:00:00');
        $transaction5->setOrderStatus(TransactionStatus::CONFIRMED);
        $transaction5->setCurrency(Currency::CNY);
        $transaction5->setTag('electronics');
        $transaction5->setCategoryId('1001');
        $transaction5->setCategoryName('电子产品');
        $transaction5->setItemQuantity(1);
        $transaction5->setItemName('小米电视 55寸');
        $transaction5->setOriginalCurrency(Currency::CNY);
        $transaction5->setOriginalTotalPrice('1299.00');
        $transaction5->setBalanceTime('2023-11-05');
        $transaction5->setCampaign($campaign2);
        $manager->persist($transaction5);
        $this->addReference(self::TRANSACTION_5, $transaction5);

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
