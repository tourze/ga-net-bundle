<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\GaNetBundle\Enum\SettlementStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(SettlementStatus::class)]
class SettlementStatusTest extends AbstractEnumTestCase
{
    public function testEnumValues(): void
    {
        $this->assertSame(1, SettlementStatus::PENDING->value);
        $this->assertSame(2, SettlementStatus::APPROVED->value);
        $this->assertSame(3, SettlementStatus::REJECTED->value);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('待认证', SettlementStatus::PENDING->getLabel());
        $this->assertSame('已通过', SettlementStatus::APPROVED->getLabel());
        $this->assertSame('已拒绝', SettlementStatus::REJECTED->getLabel());
    }

    public function testGetDescription(): void
    {
        $this->assertSame('等待审核认证', SettlementStatus::PENDING->getDescription());
        $this->assertSame('已审核通过', SettlementStatus::APPROVED->getDescription());
        $this->assertSame('已拒绝处理', SettlementStatus::REJECTED->getDescription());
    }

    public function testIsPending(): void
    {
        $this->assertTrue(SettlementStatus::PENDING->isPending());
        $this->assertFalse(SettlementStatus::APPROVED->isPending());
        $this->assertFalse(SettlementStatus::REJECTED->isPending());
    }

    public function testIsApproved(): void
    {
        $this->assertTrue(SettlementStatus::APPROVED->isApproved());
        $this->assertFalse(SettlementStatus::PENDING->isApproved());
        $this->assertFalse(SettlementStatus::REJECTED->isApproved());
    }

    public function testIsRejected(): void
    {
        $this->assertTrue(SettlementStatus::REJECTED->isRejected());
        $this->assertFalse(SettlementStatus::PENDING->isRejected());
        $this->assertFalse(SettlementStatus::APPROVED->isRejected());
    }

    public function testCases(): void
    {
        $cases = SettlementStatus::cases();
        $this->assertCount(3, $cases);
        $this->assertContains(SettlementStatus::PENDING, $cases);
        $this->assertContains(SettlementStatus::APPROVED, $cases);
        $this->assertContains(SettlementStatus::REJECTED, $cases);
    }

    public function testFromValue(): void
    {
        $this->assertSame(SettlementStatus::PENDING, SettlementStatus::from(1));
        $this->assertSame(SettlementStatus::APPROVED, SettlementStatus::from(2));
        $this->assertSame(SettlementStatus::REJECTED, SettlementStatus::from(3));
    }

    public function testToArray(): void
    {
        $expected = [
            'value' => 1,
            'label' => '待认证',
        ];
        $this->assertSame($expected, SettlementStatus::PENDING->toArray());

        $expected = [
            'value' => 2,
            'label' => '已通过',
        ];
        $this->assertSame($expected, SettlementStatus::APPROVED->toArray());

        $expected = [
            'value' => 3,
            'label' => '已拒绝',
        ];
        $this->assertSame($expected, SettlementStatus::REJECTED->toArray());
    }
}
