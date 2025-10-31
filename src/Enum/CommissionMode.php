<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * GA Net 佣金模式枚举
 */
enum CommissionMode: int implements Labelable, Itemable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;

    case PERCENTAGE = 1;
    case FIXED = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::PERCENTAGE => '分成',
            self::FIXED => '固定',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::PERCENTAGE => '按比例分成',
            self::FIXED => '固定金额',
        };
    }

    public function isPercentage(): bool
    {
        return self::PERCENTAGE === $this;
    }

    public function isFixed(): bool
    {
        return self::FIXED === $this;
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::PERCENTAGE => self::PRIMARY,
            self::FIXED => self::SUCCESS,
        };
    }
}
