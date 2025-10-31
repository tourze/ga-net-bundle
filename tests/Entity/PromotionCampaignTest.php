<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\GaNetBundle\Entity\PromotionCampaign;
use Tourze\GaNetBundle\Entity\Publisher;
use Tourze\GaNetBundle\Enum\PromotionType;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(PromotionCampaign::class)]
class PromotionCampaignTest extends AbstractEntityTestCase
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

        return new PromotionCampaign();
        // Note: PromotionCampaign doesn't have a setPublisher method based on the entity structure
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'id' => ['id', 1];
        yield 'promotionType' => ['promotionType', PromotionType::COUPON];
        yield 'startTime' => ['startTime', '2022-09-30 19:30:00'];
        yield 'endTime' => ['endTime', '2022-11-29 18:30:00'];
        yield 'title' => ['title', '15% Off On Monastery Of El Escorial'];
        yield 'image' => ['image', 'https://example.com/promo.jpg'];
        yield 'url' => ['url', 'https://example.com/deal'];
        yield 'description' => ['description', 'Combine the two most popular day tours'];
        yield 'couponCode' => ['couponCode', 'AFFLJN8I'];
        yield 'campaignId' => ['campaignId', 3356];
    }

    public function testPromotionCampaignCreation(): void
    {
        $promotion = new PromotionCampaign();
        $promotion->setId(1);
        $promotion->setPublisher($this->publisher);
        $promotion->setPromotionType(PromotionType::COUPON);
        $promotion->setStartTime('2022-09-30 19:30:00');
        $promotion->setEndTime('2022-11-29 18:30:00');
        $promotion->setName('15% Off On Monastery Of El Escorial');
        $promotion->setTitle('15% Off On Monastery Of El Escorial');
        $promotion->setCouponCode('AFFLJN8I');
        $promotion->setCampaignId(3356);
        $promotion->setCreateTime(new \DateTimeImmutable());

        $this->assertSame(1, $promotion->getId());
        $this->assertSame(PromotionType::COUPON, $promotion->getPromotionType());
        $this->assertSame('2022-09-30 19:30:00', $promotion->getStartTime());
        $this->assertSame('2022-11-29 18:30:00', $promotion->getEndTime());
        $this->assertSame('15% Off On Monastery Of El Escorial', $promotion->getTitle());
        $this->assertSame('AFFLJN8I', $promotion->getCouponCode());
        $this->assertSame(3356, $promotion->getCampaignId());
        $this->assertSame($this->publisher, $promotion->getPublisher());
        $this->assertInstanceOf(\DateTimeInterface::class, $promotion->getCreateTime());
    }

    public function testPromotionTypeConstants(): void
    {
        $this->assertSame(1, PromotionType::DISCOUNT->value);
        $this->assertSame(2, PromotionType::COUPON->value);
    }

    public function testPromotionTypeCheckers(): void
    {
        $promotion = new PromotionCampaign();
        $promotion->setPublisher($this->publisher);

        $promotion->setPromotionType(PromotionType::DISCOUNT);
        $this->assertTrue($promotion->isDiscountType());
        $this->assertFalse($promotion->isCouponType());
        $this->assertSame('降价/打折', $promotion->getPromotionTypeLabel());

        $promotion->setPromotionType(PromotionType::COUPON);
        $this->assertFalse($promotion->isDiscountType());
        $this->assertTrue($promotion->isCouponType());
        $this->assertSame('优惠券', $promotion->getPromotionTypeLabel());
    }

    public function testUpdateFromApiData(): void
    {
        $promotion = new PromotionCampaign();
        $promotion->setPublisher($this->publisher);
        $promotion->setId(1);

        $apiData = [
            'promotion_type' => 2,
            'start_time' => '2022-09-30 19:30:00',
            'end_time' => '2022-11-29 18:30:00',
            'title' => '15% Off On Monastery Of El Escorial',
            'image' => 'https://example.com/promo.jpg',
            'url' => 'https://example.com/deal',
            'description' => 'Combine the two most popular day tours',
            'coupon_code' => 'AFFLJN8I',
            'campaign_id' => 3356,
        ];

        $promotion->updateFromApiData($apiData);
        $promotion->setUpdateTime(new \DateTimeImmutable());

        $this->assertSame(PromotionType::COUPON, $promotion->getPromotionType());
        $this->assertSame('2022-09-30 19:30:00', $promotion->getStartTime());
        $this->assertSame('2022-11-29 18:30:00', $promotion->getEndTime());
        $this->assertSame('15% Off On Monastery Of El Escorial', $promotion->getTitle());
        $this->assertSame('https://example.com/promo.jpg', $promotion->getImage());
        $this->assertSame('https://example.com/deal', $promotion->getUrl());
        $this->assertSame('Combine the two most popular day tours', $promotion->getDescription());
        $this->assertSame('AFFLJN8I', $promotion->getCouponCode());
        $this->assertSame(3356, $promotion->getCampaignId());
        $this->assertInstanceOf(\DateTimeInterface::class, $promotion->getUpdateTime());
    }

    public function testUpdateFromApiDataWithDefaults(): void
    {
        $promotion = new PromotionCampaign();
        $promotion->setPublisher($this->publisher);
        $promotion->updateFromApiData([]);

        $this->assertSame(PromotionType::DISCOUNT, $promotion->getPromotionType());
        $this->assertSame('', $promotion->getStartTime());
        $this->assertSame('', $promotion->getEndTime());
        $this->assertSame('', $promotion->getTitle());
        $this->assertNull($promotion->getImage());
        $this->assertNull($promotion->getUrl());
        $this->assertNull($promotion->getDescription());
        $this->assertNull($promotion->getCouponCode());
        $this->assertNull($promotion->getCampaignId());
    }
}
