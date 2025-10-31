<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\GaNetBundle\Enum\YesNoFlag;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(YesNoFlag::class)]
final class YesNoFlagTest extends AbstractEnumTestCase
{
    #[Test]
    public function testEnumValuesShouldBeCorrect(): void
    {
        $this->assertSame(1, YesNoFlag::YES->value);
        $this->assertSame(2, YesNoFlag::NO->value);
    }

    #[Test]
    public function testGetLabelShouldReturnCorrectLabels(): void
    {
        $this->assertSame('是', YesNoFlag::YES->getLabel());
        $this->assertSame('否', YesNoFlag::NO->getLabel());
    }

    #[Test]
    public function testIsYesShouldReturnTrueOnlyForYes(): void
    {
        $this->assertTrue(YesNoFlag::YES->isYes());
        $this->assertFalse(YesNoFlag::NO->isYes());
    }

    #[Test]
    public function testIsNoShouldReturnTrueOnlyForNo(): void
    {
        $this->assertTrue(YesNoFlag::NO->isNo());
        $this->assertFalse(YesNoFlag::YES->isNo());
    }

    #[Test]
    public function testFromValueShouldCreateCorrectEnumInstance(): void
    {
        $this->assertSame(YesNoFlag::YES, YesNoFlag::from(1));
        $this->assertSame(YesNoFlag::NO, YesNoFlag::from(2));
    }

    #[Test]
    public function testTryFromShouldReturnNullForInvalidValue(): void
    {
        $this->assertNull(YesNoFlag::tryFrom(0));
        $this->assertNull(YesNoFlag::tryFrom(3));
        $this->assertNull(YesNoFlag::tryFrom(-1));
        $this->assertNull(YesNoFlag::tryFrom(99));
    }

    #[Test]
    public function testTryFromShouldReturnCorrectEnumForValidValue(): void
    {
        $this->assertSame(YesNoFlag::YES, YesNoFlag::tryFrom(1));
        $this->assertSame(YesNoFlag::NO, YesNoFlag::tryFrom(2));
    }

    #[Test]
    public function testGetCasesShouldReturnAllEnumCases(): void
    {
        $cases = YesNoFlag::cases();

        $this->assertCount(2, $cases);
        $this->assertContains(YesNoFlag::YES, $cases);
        $this->assertContains(YesNoFlag::NO, $cases);
    }

    #[Test]
    public function testEnumImplementsExpectedInterfaces(): void
    {
        $enum = YesNoFlag::YES;

        $this->assertInstanceOf(Labelable::class, $enum);
        $this->assertInstanceOf(Itemable::class, $enum);
        $this->assertInstanceOf(Selectable::class, $enum);
    }

    #[Test]
    public function testBooleanLogicShouldWorkCorrectly(): void
    {
        // 测试与boolean值的转换逻辑
        $yesFlag = YesNoFlag::YES;
        $noFlag = YesNoFlag::NO;

        $this->assertTrue($yesFlag->isYes());
        $this->assertFalse($yesFlag->isNo());

        $this->assertTrue($noFlag->isNo());
        $this->assertFalse($noFlag->isYes());
    }

    #[Test]
    public function testAllFlagsHaveUniqueValuesAndLabels(): void
    {
        $allFlags = YesNoFlag::cases();

        // 验证每个标志都有唯一的值和标签
        $values = array_map(fn ($flag) => $flag->value, $allFlags);
        $labels = array_map(fn ($flag) => $flag->getLabel(), $allFlags);

        $this->assertSame(count($values), count(array_unique($values)), 'All flag values should be unique');
        $this->assertSame(count($labels), count(array_unique($labels)), 'All flag labels should be unique');
    }

    #[Test]
    public function testFlagValuesFollowExpectedPattern(): void
    {
        // 验证标志值的合理性
        $this->assertSame(1, YesNoFlag::YES->value);
        $this->assertSame(2, YesNoFlag::NO->value);

        // 验证值都是正整数
        foreach (YesNoFlag::cases() as $flag) {
            $this->assertIsInt($flag->value);
            $this->assertGreaterThan(0, $flag->value);
        }
    }

    #[Test]
    public function testBusinessLogicIntegration(): void
    {
        // 测试在实际业务中的使用场景
        $isActive = YesNoFlag::YES;

        // 模拟数据库存储和读取
        $storedValue = $isActive->value;
        $retrievedFlag = YesNoFlag::from($storedValue);

        $this->assertSame($isActive, $retrievedFlag);
        $this->assertTrue($retrievedFlag->isYes());
        $this->assertSame('是', $retrievedFlag->getLabel());
    }

    #[Test]
    public function testConditionalLogicUsage(): void
    {
        // 测试在条件判断中的使用
        $isEnabled = YesNoFlag::YES;
        $isDisabled = YesNoFlag::NO;

        // 模拟业务逻辑判断
        if ($isEnabled->isYes()) {
            $result = 'feature_enabled';
        } else {
            $result = 'feature_disabled';
        }
        $this->assertSame('feature_enabled', $result);

        if ($isDisabled->isNo()) {
            $result = 'feature_disabled';
        } else {
            $result = 'feature_enabled';
        }
        $this->assertSame('feature_disabled', $result);
    }

    #[Test]
    public function testFilteringLogicShouldWorkCorrectly(): void
    {
        // 测试在筛选中的使用
        $flags = [YesNoFlag::YES, YesNoFlag::NO, YesNoFlag::YES, YesNoFlag::NO];

        // 筛选出所有“是”的标志
        $yesFlags = array_filter($flags, fn ($flag) => $flag->isYes());
        $this->assertCount(2, $yesFlags);

        // 筛选出所有“否”的标志
        $noFlags = array_filter($flags, fn ($flag) => $flag->isNo());
        $this->assertCount(2, $noFlags);
    }

    #[Test]
    public function testComparisonLogicShouldWorkCorrectly(): void
    {
        // 测试比较逻辑
        $yes1 = YesNoFlag::YES;
        $yes2 = YesNoFlag::YES;
        $no = YesNoFlag::NO;

        $this->assertSame($yes1, $yes2);
        $this->assertNotEquals($yes1->value, $no->value);

        // 测试值比较
        $this->assertTrue($yes1->value === $yes2->value);
        $this->assertFalse($yes1->value === $no->value);
    }

    #[Test]
    public function testFormSelectOptionsGeneration(): void
    {
        // 测试为表单选择框生成选项的场景
        $allFlags = YesNoFlag::cases();
        $options = [];

        foreach ($allFlags as $flag) {
            $options[$flag->getLabel()] = $flag->value;
        }

        $expected = ['是' => 1, '否' => 2];
        $this->assertSame($expected, $options);
    }

    #[Test]
    final public function testToArray(): void
    {
        $className = self::getEnumClass();
        // Call cases() which is the standard method for enums
        $cases = $className::cases();
        $this->assertIsArray($cases);

        // Test that we can convert enum cases to array-like structure
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

        // 测试实际的业务逻辑：数组结构的正确性和完整性
        $this->assertCount(2, $enumValues, 'Array should contain exactly 2 yes/no flag entries');
        $this->assertArrayHasKey('YES', $enumValues);
        $this->assertArrayHasKey('NO', $enumValues);

        // 验证值的类型和内容正确性：业务标志的数值表示
        $this->assertSame(1, $enumValues['YES']);
        $this->assertSame(2, $enumValues['NO']);

        // 验证业务逻辑：数值范围和間隔的合理性
        $values = array_values($enumValues);
        $this->assertSame([1, 2], $values, 'Flag values should be consecutive integers 1 and 2');

        // 验证在优先级排序中的使用
        $sortedByValue = $enumValues;
        asort($sortedByValue);
        $expectedOrder = ['YES' => 1, 'NO' => 2];
        $this->assertSame($expectedOrder, $sortedByValue, 'YES should come before NO in value-sorted order');

        // 验证数组在实际业务中的使用场景：作为表单选项
        $flipCompatibleValues = array_filter($enumValues, static fn ($value) => is_int($value));
        $formOptions = array_flip($flipCompatibleValues); // 交换键值用于表单
        $this->assertSame([1 => 'YES', 2 => 'NO'], $formOptions);

        // 确保没有多余的键
        $expectedKeys = ['YES', 'NO'];
        $this->assertSame($expectedKeys, array_keys($enumValues), 'Array keys should match expected flag names');
    }
}
