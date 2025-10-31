<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * GA Net 交易状态枚举
 */
enum TransactionStatus: int implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 1;
    case CONFIRMED = 2;
    case REJECTED = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待认证',
            self::CONFIRMED => '已认证',
            self::REJECTED => '拒绝',
        };
    }

    public function isPending(): bool
    {
        return self::PENDING === $this;
    }

    public function isConfirmed(): bool
    {
        return self::CONFIRMED === $this;
    }

    public function isRejected(): bool
    {
        return self::REJECTED === $this;
    }
}
