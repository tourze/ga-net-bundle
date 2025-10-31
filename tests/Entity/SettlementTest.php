<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Entity\Settlement;
use Tourze\GaNetBundle\Enum\SettlementStatus;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Settlement::class)]
class SettlementTest extends AbstractEntityTestCase
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

        $settlement = new Settlement();
        $settlement->setPublisher($publisher);

        return $settlement;
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'id' => ['id', 7402226];
        yield 'orderId' => ['orderId', 'NN18074204741550054'];
        yield 'websiteId' => ['websiteId', 218];
        yield 'totalPrice' => ['totalPrice', '80.0'];
        yield 'campaignId' => ['campaignId', 2916];
        yield 'campaignName' => ['campaignName', '努比亚'];
        yield 'totalCommission' => ['totalCommission', '2.8'];
        yield 'orderTime' => ['orderTime', '2019-01-04 20:47:04 +0000'];
        yield 'orderStatus' => ['orderStatus', SettlementStatus::APPROVED];
        yield 'currency' => ['currency', 'CNY'];
        yield 'tag' => ['tag', ''];
        yield 'balanceTime' => ['balanceTime', '2019-02'];
        yield 'categoryId' => ['categoryId', ''];
        yield 'categoryName' => ['categoryName', ''];
        yield 'itemQuantity' => ['itemQuantity', 1];
        yield 'itemName' => ['itemName', '努比亚圈铁耳机(配件)'];
        yield 'originalCurrency' => ['originalCurrency', 'CNY'];
        yield 'originalTotalPrice' => ['originalTotalPrice', '80.0'];
    }

    public function testSettlementCreation(): void
    {
        $settlement = new Settlement();
        $settlement->setPublisher($this->publisher);
        $settlement->setId(7402226);
        $settlement->setOrderId('cancel_order_201901-');
        $settlement->setWebsiteId(218);
        $settlement->setTotalPrice('0');
        $settlement->setCampaignId(2971);
        $settlement->setCampaignName('Rakuten Global Market');
        $settlement->setTotalCommission('0');
        $settlement->setOrderTime('2019-01-01 00:03:16 +0000');
        $settlement->setOrderStatus(SettlementStatus::APPROVED);
        $settlement->setCurrency('JPY');
        $settlement->setBalanceTime('2019-02');
        $settlement->setCreateTime(new \DateTimeImmutable());

        $this->assertSame(7402226, $settlement->getId());
        $this->assertSame('cancel_order_201901-', $settlement->getOrderId());
        $this->assertSame(218, $settlement->getWebsiteId());
        $this->assertSame('0', $settlement->getTotalPrice());
        $this->assertSame(2971, $settlement->getCampaignId());
        $this->assertSame('Rakuten Global Market', $settlement->getCampaignName());
        $this->assertSame('0', $settlement->getTotalCommission());
        $this->assertSame('2019-01-01 00:03:16 +0000', $settlement->getOrderTime());
        $this->assertSame(SettlementStatus::APPROVED, $settlement->getOrderStatus());
        $this->assertSame('JPY', $settlement->getCurrency());
        $this->assertSame('2019-02', $settlement->getBalanceTime());
        $this->assertSame($this->publisher, $settlement->getPublisher());
        $this->assertInstanceOf(\DateTimeInterface::class, $settlement->getCreateTime());
    }

    public function testStatusEnumValues(): void
    {
        $this->assertSame(1, SettlementStatus::PENDING->value);
        $this->assertSame(2, SettlementStatus::APPROVED->value);
        $this->assertSame(3, SettlementStatus::REJECTED->value);
    }

    public function testStatusCheckers(): void
    {
        $settlement = new Settlement();
        $settlement->setPublisher($this->publisher);

        $settlement->setOrderStatus(SettlementStatus::PENDING);
        $this->assertTrue($settlement->isPending());
        $this->assertFalse($settlement->isApproved());
        $this->assertFalse($settlement->isRejected());
        $this->assertSame('待认证', $settlement->getStatusLabel());

        $settlement->setOrderStatus(SettlementStatus::APPROVED);
        $this->assertFalse($settlement->isPending());
        $this->assertTrue($settlement->isApproved());
        $this->assertFalse($settlement->isRejected());
        $this->assertSame('已通过', $settlement->getStatusLabel());

        $settlement->setOrderStatus(SettlementStatus::REJECTED);
        $this->assertFalse($settlement->isPending());
        $this->assertFalse($settlement->isApproved());
        $this->assertTrue($settlement->isRejected());
        $this->assertSame('已拒绝', $settlement->getStatusLabel());
    }

    public function testUpdateFromApiData(): void
    {
        $settlement = new Settlement();
        $settlement->setPublisher($this->publisher);
        $settlement->setId(7402401);

        $apiData = [
            'order_id' => 'NN18074204741550054',
            'website_id' => 218,
            'total_price' => '80.0',
            'campaign_id' => 2916,
            'campaign_name' => '努比亚',
            'total_commission' => '2.8',
            'order_time' => '2019-01-04 20:47:04 +0000',
            'order_status' => 2,
            'currency' => 'CNY',
            'tag' => '',
            'balance_time' => '2019-02',
            'category_id' => '',
            'category_name' => '',
            'item_quantity' => 1,
            'item_name' => '努比亚圈铁耳机(配件)',
            'original_currency' => 'CNY',
            'original_total_price' => '80.0',
        ];

        $settlement->updateFromApiData($apiData);
        $settlement->setUpdateTime(new \DateTimeImmutable());

        $this->assertSame('NN18074204741550054', $settlement->getOrderId());
        $this->assertSame(218, $settlement->getWebsiteId());
        $this->assertSame('80.0', $settlement->getTotalPrice());
        $this->assertSame(2916, $settlement->getCampaignId());
        $this->assertSame('努比亚', $settlement->getCampaignName());
        $this->assertSame('2.8', $settlement->getTotalCommission());
        $this->assertSame('2019-01-04 20:47:04 +0000', $settlement->getOrderTime());
        $this->assertSame(SettlementStatus::APPROVED, $settlement->getOrderStatus());
        $this->assertSame('CNY', $settlement->getCurrency());
        $this->assertSame('', $settlement->getTag());
        $this->assertSame('2019-02', $settlement->getBalanceTime());
        $this->assertSame('', $settlement->getCategoryId());
        $this->assertSame('', $settlement->getCategoryName());
        $this->assertSame(1, $settlement->getItemQuantity());
        $this->assertSame('努比亚圈铁耳机(配件)', $settlement->getItemName());
        $this->assertSame('CNY', $settlement->getOriginalCurrency());
        $this->assertSame('80.0', $settlement->getOriginalTotalPrice());
        $this->assertInstanceOf(\DateTimeInterface::class, $settlement->getUpdateTime());
    }

    public function testUpdateFromApiDataWithDefaults(): void
    {
        $settlement = new Settlement();
        $settlement->setPublisher($this->publisher);
        $settlement->updateFromApiData([]);

        $this->assertSame('', $settlement->getOrderId());
        $this->assertSame(0, $settlement->getWebsiteId());
        $this->assertSame('0', $settlement->getTotalPrice());
        $this->assertNull($settlement->getCampaignId());
        $this->assertSame('', $settlement->getCampaignName());
        $this->assertSame('0', $settlement->getTotalCommission());
        $this->assertSame('', $settlement->getOrderTime());
        $this->assertSame(SettlementStatus::PENDING, $settlement->getOrderStatus());
        $this->assertSame('', $settlement->getCurrency());
        $this->assertNull($settlement->getTag());
        $this->assertSame('', $settlement->getBalanceTime());
        $this->assertNull($settlement->getCategoryId());
        $this->assertNull($settlement->getCategoryName());
        $this->assertSame(0, $settlement->getItemQuantity());
        $this->assertSame('', $settlement->getItemName());
        $this->assertSame('', $settlement->getOriginalCurrency());
        $this->assertSame('0', $settlement->getOriginalTotalPrice());
    }
}
