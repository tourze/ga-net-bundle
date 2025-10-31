<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * GA Net 是否标识枚举
 */
enum YesNoFlag: int implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case YES = 1;
    case NO = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::YES => '是',
            self::NO => '否',
        };
    }

    public function isYes(): bool
    {
        return self::YES === $this;
    }

    public function isNo(): bool
    {
        return self::NO === $this;
    }
}
