<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\Transaction;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\GaNetBundle\Enum\TransactionStatus;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Transaction::class)]
class TransactionTest extends AbstractEntityTestCase
{
    private Publisher $publisher;

    protected function setUp(): void
    {
        parent::setUp();

        $publisherId = time() + rand(1, 1000);
        $this->publisher = new Publisher();
        $this->publisher->setPublisherId($publisherId);
        $this->publisher->setToken("test-token-{$publisherId}");
    }

    protected function createEntity(): object
    {
        $publisherId = time() + rand(1, 1000);
        $publisher = new Publisher();
        $publisher->setPublisherId($publisherId);
        $publisher->setToken("test-token-{$publisherId}");

        $transaction = new Transaction();
        $transaction->setPublisher($publisher);

        return $transaction;
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'id' => ['id', 6841023];
        yield 'memo' => ['memo', '{"platform":"PHONE"}'];
        yield 'orderId' => ['orderId', '101287743697_1517414400'];
        yield 'websiteId' => ['websiteId', 218];
        yield 'totalPrice' => ['totalPrice', '2480.0'];
        yield 'campaignId' => ['campaignId', 2914];
        yield 'campaignName' => ['campaignName', '亚马逊（日本）'];
        yield 'totalCommission' => ['totalCommission', '99.2'];
        yield 'orderTime' => ['orderTime', '2018-12-01 00:00:00 +0000'];
        yield 'orderStatus' => ['orderStatus', TransactionStatus::CONFIRMED];
        yield 'currency' => ['currency', Currency::CNY];
        yield 'tag' => ['tag', '0|'];
        yield 'categoryId' => ['categoryId', ''];
        yield 'categoryName' => ['categoryName', ''];
        yield 'itemQuantity' => ['itemQuantity', 1];
        yield 'itemName' => ['itemName', '[B01MYESM2H]アネッサ パーフェクトUV'];
        yield 'originalCurrency' => ['originalCurrency', Currency::CNY];
        yield 'originalTotalPrice' => ['originalTotalPrice', '2480.0'];
        yield 'balanceTime' => ['balanceTime', '2019-02'];
    }

    public function testTransactionCreation(): void
    {
        $transaction = new Transaction();
        $transaction->setPublisher($this->publisher);
        $transaction->setId(6841023);
        $transaction->setOrderId('101287743697_1517414400');
        $transaction->setWebsiteId(218);
        $transaction->setTotalPrice('2480.0');
        $transaction->setCampaignId(2914);
        $transaction->setCampaignName('亚马逊（日本）');
        $transaction->setTotalCommission('99.2');
        $transaction->setOrderTime('2018-12-01 00:00:00 +0000');
        $transaction->setOrderStatus(TransactionStatus::CONFIRMED);
        $transaction->setCurrency(Currency::USD);
        $transaction->setCreateTime(new \DateTimeImmutable());

        $this->assertSame(6841023, $transaction->getId());
        $this->assertSame('101287743697_1517414400', $transaction->getOrderId());
        $this->assertSame(218, $transaction->getWebsiteId());
        $this->assertSame('2480.0', $transaction->getTotalPrice());
        $this->assertSame(2914, $transaction->getCampaignId());
        $this->assertSame('亚马逊（日本）', $transaction->getCampaignName());
        $this->assertSame('99.2', $transaction->getTotalCommission());
        $this->assertSame('2018-12-01 00:00:00 +0000', $transaction->getOrderTime());
        $this->assertSame(TransactionStatus::CONFIRMED, $transaction->getOrderStatus());
        $this->assertSame(Currency::USD, $transaction->getCurrency());
        $this->assertSame($this->publisher, $transaction->getPublisher());
        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->getCreateTime());
    }

    public function testStatusConstants(): void
    {
        $this->assertSame(1, TransactionStatus::PENDING->value);
        $this->assertSame(2, TransactionStatus::CONFIRMED->value);
        $this->assertSame(3, TransactionStatus::REJECTED->value);
    }

    public function testStatusCheckers(): void
    {
        $transaction = new Transaction();
        $transaction->setPublisher($this->publisher);

        $transaction->setOrderStatus(TransactionStatus::PENDING);
        $this->assertTrue($transaction->isPending());
        $this->assertFalse($transaction->isConfirmed());
        $this->assertFalse($transaction->isRejected());
        $this->assertSame('待认证', $transaction->getStatusLabel());

        $transaction->setOrderStatus(TransactionStatus::CONFIRMED);
        $this->assertFalse($transaction->isPending());
        $this->assertTrue($transaction->isConfirmed());
        $this->assertFalse($transaction->isRejected());
        $this->assertSame('已认证', $transaction->getStatusLabel());

        $transaction->setOrderStatus(TransactionStatus::REJECTED);
        $this->assertFalse($transaction->isPending());
        $this->assertFalse($transaction->isConfirmed());
        $this->assertTrue($transaction->isRejected());
        $this->assertSame('拒绝', $transaction->getStatusLabel());
    }

    public function testSettlementStatus(): void
    {
        $transaction = new Transaction();
        $transaction->setPublisher($this->publisher);

        $this->assertFalse($transaction->isSettled());

        $transaction->setBalanceTime('2019-02');
        $this->assertTrue($transaction->isSettled());
    }

    public function testUpdateFromApiData(): void
    {
        $transaction = new Transaction();
        $transaction->setPublisher($this->publisher);
        $transaction->setId(6841023);

        $apiData = [
            'memo' => '{"platform":"PHONE"}',
            'order_id' => '101287743697_1517414400',
            'website_id' => 218,
            'total_price' => '2480.0',
            'campaign_id' => 2914,
            'campaign_name' => '亚马逊（日本）',
            'total_commission' => '99.2',
            'order_time' => '2018-12-01 00:00:00 +0000',
            'order_status' => 2,
            'currency' => 'USD',
            'tag' => '0|',
            'category_id' => '',
            'category_name' => '',
            'item_quantity' => 1,
            'item_name' => '[B01MYESM2H]アネッサ パーフェクトUV',
            'original_currency' => 'USD',
            'original_total_price' => '2480.0',
            'balance_time' => '2019-02',
        ];

        $transaction->updateFromApiData($apiData);
        $transaction->setUpdateTime(new \DateTimeImmutable());

        $this->assertSame('{"platform":"PHONE"}', $transaction->getMemo());
        $this->assertSame('101287743697_1517414400', $transaction->getOrderId());
        $this->assertSame(218, $transaction->getWebsiteId());
        $this->assertSame('2480.0', $transaction->getTotalPrice());
        $this->assertSame(2914, $transaction->getCampaignId());
        $this->assertSame('亚马逊（日本）', $transaction->getCampaignName());
        $this->assertSame('99.2', $transaction->getTotalCommission());
        $this->assertSame('2018-12-01 00:00:00 +0000', $transaction->getOrderTime());
        $this->assertSame(TransactionStatus::CONFIRMED, $transaction->getOrderStatus());
        $this->assertSame(Currency::USD, $transaction->getCurrency());
        $this->assertSame('0|', $transaction->getTag());
        $this->assertSame('', $transaction->getCategoryId());
        $this->assertSame('', $transaction->getCategoryName());
        $this->assertSame(1, $transaction->getItemQuantity());
        $this->assertSame('[B01MYESM2H]アネッサ パーフェクトUV', $transaction->getItemName());
        $this->assertSame(Currency::USD, $transaction->getOriginalCurrency());
        $this->assertSame('2480.0', $transaction->getOriginalTotalPrice());
        $this->assertSame('2019-02', $transaction->getBalanceTime());
        $this->assertInstanceOf(\DateTimeInterface::class, $transaction->getUpdateTime());
    }

    public function testUpdateFromApiDataWithDefaults(): void
    {
        $transaction = new Transaction();
        $transaction->setPublisher($this->publisher);
        $transaction->updateFromApiData([]);

        $this->assertNull($transaction->getMemo());
        $this->assertSame('', $transaction->getOrderId());
        $this->assertSame(0, $transaction->getWebsiteId());
        $this->assertSame('0', $transaction->getTotalPrice());
        $this->assertNull($transaction->getCampaignId());
        $this->assertSame('', $transaction->getCampaignName());
        $this->assertSame('0', $transaction->getTotalCommission());
        $this->assertSame('', $transaction->getOrderTime());
        $this->assertSame(TransactionStatus::PENDING, $transaction->getOrderStatus());
        $this->assertSame(Currency::CNY, $transaction->getCurrency());
        $this->assertNull($transaction->getTag());
        $this->assertNull($transaction->getCategoryId());
        $this->assertNull($transaction->getCategoryName());
        $this->assertSame(0, $transaction->getItemQuantity());
        $this->assertSame('', $transaction->getItemName());
        $this->assertSame(Currency::CNY, $transaction->getOriginalCurrency());
        $this->assertSame('0', $transaction->getOriginalTotalPrice());
        $this->assertNull($transaction->getBalanceTime());
    }
}
