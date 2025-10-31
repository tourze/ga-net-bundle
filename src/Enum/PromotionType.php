<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * GA Net 促销类型枚举
 */
enum PromotionType: int implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case DISCOUNT = 1;
    case COUPON = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::DISCOUNT => '降价/打折',
            self::COUPON => '优惠券',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DISCOUNT => '商品降价或打折促销',
            self::COUPON => '优惠券码促销',
        };
    }

    public function isDiscount(): bool
    {
        return self::DISCOUNT === $this;
    }

    public function isCoupon(): bool
    {
        return self::COUPON === $this;
    }
}
