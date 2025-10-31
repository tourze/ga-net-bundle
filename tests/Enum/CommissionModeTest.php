<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\GaNetBundle\Enum\CommissionMode;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(CommissionMode::class)]
class CommissionModeTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame(1, CommissionMode::PERCENTAGE->value);
        $this->assertSame(2, CommissionMode::FIXED->value);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('分成', CommissionMode::PERCENTAGE->getLabel());
        $this->assertSame('固定', CommissionMode::FIXED->getLabel());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('按比例分成', CommissionMode::PERCENTAGE->getDescription());
        $this->assertSame('固定金额', CommissionMode::FIXED->getDescription());
    }

    public function testIsPercentage(): void
    {
        $this->assertTrue(CommissionMode::PERCENTAGE->isPercentage());
        $this->assertFalse(CommissionMode::FIXED->isPercentage());
    }

    public function testIsFixed(): void
    {
        $this->assertTrue(CommissionMode::FIXED->isFixed());
        $this->assertFalse(CommissionMode::PERCENTAGE->isFixed());
    }

    public function testCases(): void
    {
        $cases = CommissionMode::cases();
        $this->assertCount(2, $cases);
        $this->assertContains(CommissionMode::PERCENTAGE, $cases);
        $this->assertContains(CommissionMode::FIXED, $cases);
    }

    public function testFromValue(): void
    {
        $this->assertSame(CommissionMode::PERCENTAGE, CommissionMode::from(1));
        $this->assertSame(CommissionMode::FIXED, CommissionMode::from(2));
    }

    public function testToArray(): void
    {
        $expected = [
            'value' => 1,
            'label' => '分成',
        ];
        $this->assertSame($expected, CommissionMode::PERCENTAGE->toArray());

        $expected = [
            'value' => 2,
            'label' => '固定',
        ];
        $this->assertSame($expected, CommissionMode::FIXED->toArray());
    }
}
