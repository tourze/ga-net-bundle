<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * GA Net 货币类型枚举
 */
enum Currency: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case CNY = 'CNY';
    case USD = 'USD';
    case EUR = 'EUR';
    case GBP = 'GBP';

    public function getLabel(): string
    {
        return match ($this) {
            self::CNY => '人民币',
            self::USD => '美元',
            self::EUR => '欧元',
            self::GBP => '英镑',
        };
    }

    public function getSymbol(): string
    {
        return match ($this) {
            self::CNY => '¥',
            self::USD => '$',
            self::EUR => '€',
            self::GBP => '£',
        };
    }
}
