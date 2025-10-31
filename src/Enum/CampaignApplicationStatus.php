<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * GA Net 活动申请状态枚举
 */
enum CampaignApplicationStatus: int implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case NOT_APPLIED = 0;
    case APPLYING = 1;
    case APPROVED = 5;
    case REJECTED = 6;

    public function getLabel(): string
    {
        return match ($this) {
            self::NOT_APPLIED => '未申请',
            self::APPLYING => '申请中',
            self::APPROVED => '申请通过',
            self::REJECTED => '申请未通过',
        };
    }

    public function isNotApplied(): bool
    {
        return self::NOT_APPLIED === $this;
    }

    public function isApplying(): bool
    {
        return self::APPLYING === $this;
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
