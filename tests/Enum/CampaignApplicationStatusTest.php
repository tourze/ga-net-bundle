<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\GaNetBundle\Enum\CampaignApplicationStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(CampaignApplicationStatus::class)]
final class CampaignApplicationStatusTest extends AbstractEnumTestCase
{
    #[Test]
    public function testEnumValuesShouldBeCorrect(): void
    {
        $this->assertSame(0, CampaignApplicationStatus::NOT_APPLIED->value);
        $this->assertSame(1, CampaignApplicationStatus::APPLYING->value);
        $this->assertSame(5, CampaignApplicationStatus::APPROVED->value);
        $this->assertSame(6, CampaignApplicationStatus::REJECTED->value);
    }

    #[Test]
    public function testGetLabelShouldReturnCorrectLabels(): void
    {
        $this->assertSame('未申请', CampaignApplicationStatus::NOT_APPLIED->getLabel());
        $this->assertSame('申请中', CampaignApplicationStatus::APPLYING->getLabel());
        $this->assertSame('申请通过', CampaignApplicationStatus::APPROVED->getLabel());
        $this->assertSame('申请未通过', CampaignApplicationStatus::REJECTED->getLabel());
    }

    #[Test]
    public function testIsNotAppliedShouldReturnTrueOnlyForNotApplied(): void
    {
        $this->assertTrue(CampaignApplicationStatus::NOT_APPLIED->isNotApplied());
        $this->assertFalse(CampaignApplicationStatus::APPLYING->isNotApplied());
        $this->assertFalse(CampaignApplicationStatus::APPROVED->isNotApplied());
        $this->assertFalse(CampaignApplicationStatus::REJECTED->isNotApplied());
    }

    #[Test]
    public function testIsApplyingShouldReturnTrueOnlyForApplying(): void
    {
        $this->assertTrue(CampaignApplicationStatus::APPLYING->isApplying());
        $this->assertFalse(CampaignApplicationStatus::NOT_APPLIED->isApplying());
        $this->assertFalse(CampaignApplicationStatus::APPROVED->isApplying());
        $this->assertFalse(CampaignApplicationStatus::REJECTED->isApplying());
    }

    #[Test]
    public function testIsApprovedShouldReturnTrueOnlyForApproved(): void
    {
        $this->assertTrue(CampaignApplicationStatus::APPROVED->isApproved());
        $this->assertFalse(CampaignApplicationStatus::NOT_APPLIED->isApproved());
        $this->assertFalse(CampaignApplicationStatus::APPLYING->isApproved());
        $this->assertFalse(CampaignApplicationStatus::REJECTED->isApproved());
    }

    #[Test]
    public function testIsRejectedShouldReturnTrueOnlyForRejected(): void
    {
        $this->assertTrue(CampaignApplicationStatus::REJECTED->isRejected());
        $this->assertFalse(CampaignApplicationStatus::NOT_APPLIED->isRejected());
        $this->assertFalse(CampaignApplicationStatus::APPLYING->isRejected());
        $this->assertFalse(CampaignApplicationStatus::APPROVED->isRejected());
    }

    #[Test]
    public function testFromValueShouldCreateCorrectEnumInstance(): void
    {
        $this->assertSame(CampaignApplicationStatus::NOT_APPLIED, CampaignApplicationStatus::from(0));
        $this->assertSame(CampaignApplicationStatus::APPLYING, CampaignApplicationStatus::from(1));
        $this->assertSame(CampaignApplicationStatus::APPROVED, CampaignApplicationStatus::from(5));
        $this->assertSame(CampaignApplicationStatus::REJECTED, CampaignApplicationStatus::from(6));
    }

    #[Test]
    public function testTryFromShouldReturnNullForInvalidValue(): void
    {
        $this->assertNull(CampaignApplicationStatus::tryFrom(99));
        $this->assertNull(CampaignApplicationStatus::tryFrom(-1));
        $this->assertNull(CampaignApplicationStatus::tryFrom(2));
    }

    #[Test]
    public function testTryFromShouldReturnCorrectEnumForValidValue(): void
    {
        $this->assertSame(CampaignApplicationStatus::NOT_APPLIED, CampaignApplicationStatus::tryFrom(0));
        $this->assertSame(CampaignApplicationStatus::APPLYING, CampaignApplicationStatus::tryFrom(1));
        $this->assertSame(CampaignApplicationStatus::APPROVED, CampaignApplicationStatus::tryFrom(5));
        $this->assertSame(CampaignApplicationStatus::REJECTED, CampaignApplicationStatus::tryFrom(6));
    }

    #[Test]
    public function testGetCasesShouldReturnAllEnumCases(): void
    {
        $cases = CampaignApplicationStatus::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(CampaignApplicationStatus::NOT_APPLIED, $cases);
        $this->assertContains(CampaignApplicationStatus::APPLYING, $cases);
        $this->assertContains(CampaignApplicationStatus::APPROVED, $cases);
        $this->assertContains(CampaignApplicationStatus::REJECTED, $cases);
    }

    #[Test]
    public function testEnumImplementsExpectedInterfaces(): void
    {
        $enum = CampaignApplicationStatus::NOT_APPLIED;

        $this->assertInstanceOf(Labelable::class, $enum);
        $this->assertInstanceOf(Itemable::class, $enum);
        $this->assertInstanceOf(Selectable::class, $enum);
    }

    #[Test]
    public function testWorkflowLogicShouldWorkCorrectly(): void
    {
        // 测试实际业务流程逻辑
        $status = CampaignApplicationStatus::NOT_APPLIED;
        $this->assertTrue($status->isNotApplied());
        $this->assertSame('未申请', $status->getLabel());

        // 模拟申请流程
        $status = CampaignApplicationStatus::APPLYING;
        $this->assertTrue($status->isApplying());
        $this->assertSame('申请中', $status->getLabel());

        // 模拟审核通过
        $status = CampaignApplicationStatus::APPROVED;
        $this->assertTrue($status->isApproved());
        $this->assertSame('申请通过', $status->getLabel());
    }

    #[Test]
    public function testStatusTransitionLogicShouldBeValid(): void
    {
        $allStatuses = CampaignApplicationStatus::cases();

        // 验证每个状态都有唯一的值和标签
        $values = array_map(fn ($status) => $status->value, $allStatuses);
        $labels = array_map(fn ($status) => $status->getLabel(), $allStatuses);

        $this->assertSame(count($values), count(array_unique($values)), 'All status values should be unique');
        $this->assertSame(count($labels), count(array_unique($labels)), 'All status labels should be unique');
    }

    #[Test]
    final public function testToArray(): void
    {
        $className = self::getEnumClass();
        $cases = $className::cases();

        $this->assertIsArray($cases, 'Enum cases should return array');

        // 测试实际转换逻辑：验证每个枚举值都能正确映射
        $enumValues = [];
        foreach ($cases as $case) {
            if (is_object($case) && property_exists($case, 'name') && property_exists($case, 'value')) {
                $name = $case->name;
                $value = $case->value;
                if ((is_string($name) || is_int($name)) && (is_string($value) || is_int($value))) {
                    $enumValues[$name] = $value;
                }
            }
        }

        // 验证关键的业务状态映射是否正确
        $this->assertSame(0, $enumValues['NOT_APPLIED']);
        $this->assertSame(1, $enumValues['APPLYING']);
        $this->assertSame(5, $enumValues['APPROVED']);
        $this->assertSame(6, $enumValues['REJECTED']);

        // 验证所有必需状态都存在
        $expectedKeys = ['NOT_APPLIED', 'APPLYING', 'APPROVED', 'REJECTED'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $enumValues, "Missing required status: {$key}");
        }
    }
}
