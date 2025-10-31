<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * GA Net 结算状态枚举
 */
enum SettlementStatus: int implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 1;
    case APPROVED = 2;
    case REJECTED = 3;

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待认证',
            self::APPROVED => '已通过',
            self::REJECTED => '已拒绝',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => '等待审核认证',
            self::APPROVED => '已审核通过',
            self::REJECTED => '已拒绝处理',
        };
    }

    public function isPending(): bool
    {
        return self::PENDING === $this;
    }

    public function isApproved(): bool
    {
        return self::APPROVED === $this;
    }

    public function isRejected(): bool
    {
        return self::REJECTED === $this;
    }
}
