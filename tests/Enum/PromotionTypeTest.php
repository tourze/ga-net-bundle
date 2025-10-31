<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\GaNetBundle\Enum\PromotionType;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(PromotionType::class)]
class PromotionTypeTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame(1, PromotionType::DISCOUNT->value);
        $this->assertSame(2, PromotionType::COUPON->value);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('降价/打折', PromotionType::DISCOUNT->getLabel());
        $this->assertSame('优惠券', PromotionType::COUPON->getLabel());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('商品降价或打折促销', PromotionType::DISCOUNT->getDescription());
        $this->assertSame('优惠券码促销', PromotionType::COUPON->getDescription());
    }

    public function testIsDiscount(): void
    {
        $this->assertTrue(PromotionType::DISCOUNT->isDiscount());
        $this->assertFalse(PromotionType::COUPON->isDiscount());
    }

    public function testIsCoupon(): void
    {
        $this->assertTrue(PromotionType::COUPON->isCoupon());
        $this->assertFalse(PromotionType::DISCOUNT->isCoupon());
    }

    public function testCases(): void
    {
        $cases = PromotionType::cases();
        $this->assertCount(2, $cases);
        $this->assertContains(PromotionType::DISCOUNT, $cases);
        $this->assertContains(PromotionType::COUPON, $cases);
    }

    public function testFromValue(): void
    {
        $this->assertSame(PromotionType::DISCOUNT, PromotionType::from(1));
        $this->assertSame(PromotionType::COUPON, PromotionType::from(2));
    }

    public function testToArray(): void
    {
        $expected = [
            'value' => 1,
            'label' => '降价/打折',
        ];
        $this->assertSame($expected, PromotionType::DISCOUNT->toArray());

        $expected = [
            'value' => 2,
            'label' => '优惠券',
        ];
        $this->assertSame($expected, PromotionType::COUPON->toArray());
    }
}
