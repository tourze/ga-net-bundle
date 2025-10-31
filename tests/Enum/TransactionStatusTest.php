<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\GaNetBundle\Enum\TransactionStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(TransactionStatus::class)]
final class TransactionStatusTest extends AbstractEnumTestCase
{
    #[Test]
    public function testEnumValuesShouldBeCorrect(): void
    {
        $this->assertSame(1, TransactionStatus::PENDING->value);
        $this->assertSame(2, TransactionStatus::CONFIRMED->value);
        $this->assertSame(3, TransactionStatus::REJECTED->value);
    }

    #[Test]
    public function testGetLabelShouldReturnCorrectLabels(): void
    {
        $this->assertSame('待认证', TransactionStatus::PENDING->getLabel());
        $this->assertSame('已认证', TransactionStatus::CONFIRMED->getLabel());
        $this->assertSame('拒绝', TransactionStatus::REJECTED->getLabel());
    }

    #[Test]
    public function testIsPendingShouldReturnTrueOnlyForPending(): void
    {
        $this->assertTrue(TransactionStatus::PENDING->isPending());
        $this->assertFalse(TransactionStatus::CONFIRMED->isPending());
        $this->assertFalse(TransactionStatus::REJECTED->isPending());
    }

    #[Test]
    public function testIsConfirmedShouldReturnTrueOnlyForConfirmed(): void
    {
        $this->assertTrue(TransactionStatus::CONFIRMED->isConfirmed());
        $this->assertFalse(TransactionStatus::PENDING->isConfirmed());
        $this->assertFalse(TransactionStatus::REJECTED->isConfirmed());
    }

    #[Test]
    public function testIsRejectedShouldReturnTrueOnlyForRejected(): void
    {
        $this->assertTrue(TransactionStatus::REJECTED->isRejected());
        $this->assertFalse(TransactionStatus::PENDING->isRejected());
        $this->assertFalse(TransactionStatus::CONFIRMED->isRejected());
    }

    #[Test]
    public function testFromValueShouldCreateCorrectEnumInstance(): void
    {
        $this->assertSame(TransactionStatus::PENDING, TransactionStatus::from(1));
        $this->assertSame(TransactionStatus::CONFIRMED, TransactionStatus::from(2));
        $this->assertSame(TransactionStatus::REJECTED, TransactionStatus::from(3));
    }

    #[Test]
    public function testTryFromShouldReturnNullForInvalidValue(): void
    {
        $this->assertNull(TransactionStatus::tryFrom(0));
        $this->assertNull(TransactionStatus::tryFrom(4));
        $this->assertNull(TransactionStatus::tryFrom(-1));
        $this->assertNull(TransactionStatus::tryFrom(99));
    }

    #[Test]
    public function testTryFromShouldReturnCorrectEnumForValidValue(): void
    {
        $this->assertSame(TransactionStatus::PENDING, TransactionStatus::tryFrom(1));
        $this->assertSame(TransactionStatus::CONFIRMED, TransactionStatus::tryFrom(2));
        $this->assertSame(TransactionStatus::REJECTED, TransactionStatus::tryFrom(3));
    }

    #[Test]
    public function testGetCasesShouldReturnAllEnumCases(): void
    {
        $cases = TransactionStatus::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(TransactionStatus::PENDING, $cases);
        $this->assertContains(TransactionStatus::CONFIRMED, $cases);
        $this->assertContains(TransactionStatus::REJECTED, $cases);
    }

    #[Test]
    public function testEnumImplementsExpectedInterfaces(): void
    {
        $enum = TransactionStatus::PENDING;

        $this->assertInstanceOf(Labelable::class, $enum);
        $this->assertInstanceOf(Itemable::class, $enum);
        $this->assertInstanceOf(Selectable::class, $enum);
    }

    #[Test]
    public function testTransactionWorkflowShouldBeLogical(): void
    {
        // 测试交易状态流程逻辑
        $status = TransactionStatus::PENDING;
        $this->assertTrue($status->isPending());
        $this->assertSame('待认证', $status->getLabel());

        // 模拟审核通过
        $status = TransactionStatus::CONFIRMED;
        $this->assertTrue($status->isConfirmed());
        $this->assertSame('已认证', $status->getLabel());

        // 模拟审核拒绝
        $status = TransactionStatus::REJECTED;
        $this->assertTrue($status->isRejected());
        $this->assertSame('拒绝', $status->getLabel());
    }

    #[Test]
    public function testAllStatusesHaveUniqueValuesAndLabels(): void
    {
        $allStatuses = TransactionStatus::cases();

        // 验证每个状态都有唯一的值和标签
        $values = array_map(fn ($status) => $status->value, $allStatuses);
        $labels = array_map(fn ($status) => $status->getLabel(), $allStatuses);

        $this->assertSame(count($values), count(array_unique($values)), 'All status values should be unique');
        $this->assertSame(count($labels), count(array_unique($labels)), 'All status labels should be unique');
    }

    #[Test]
    public function testStatusValuesFollowExpectedSequence(): void
    {
        // 验证状态值的顺序和合理性
        $this->assertSame(1, TransactionStatus::PENDING->value);
        $this->assertSame(2, TransactionStatus::CONFIRMED->value);
        $this->assertSame(3, TransactionStatus::REJECTED->value);

        // 验证值都是正整数
        foreach (TransactionStatus::cases() as $status) {
            $this->assertIsInt($status->value);
            $this->assertGreaterThan(0, $status->value);
        }
    }

    #[Test]
    public function testStatusBusinessLogicIntegration(): void
    {
        // 测试在实际业务中的使用场景
        $status = TransactionStatus::CONFIRMED;

        // 模拟数据库存储和读取
        $storedValue = $status->value;
        $retrievedStatus = TransactionStatus::from($storedValue);

        $this->assertSame($status, $retrievedStatus);
        $this->assertTrue($retrievedStatus->isConfirmed());
        $this->assertSame('已认证', $retrievedStatus->getLabel());
    }

    #[Test]
    public function testStatusFilteringLogicShouldWorkCorrectly(): void
    {
        // 测试在筛选和统计中的使用
        $allStatuses = TransactionStatus::cases();

        // 模拟获取可结算的状态（一般只有CONFIRMED）
        $settlableStatuses = array_filter($allStatuses, fn ($status) => $status->isConfirmed());
        $this->assertCount(1, $settlableStatuses);
        $this->assertContains(TransactionStatus::CONFIRMED, $settlableStatuses);

        // 模拟获取未完成的状态
        $incompleteStatuses = array_filter($allStatuses, fn ($status) => !$status->isConfirmed());
        $this->assertCount(2, $incompleteStatuses);
        $this->assertContains(TransactionStatus::PENDING, $incompleteStatuses);
        $this->assertContains(TransactionStatus::REJECTED, $incompleteStatuses);
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
        $this->assertCount(3, $enumValues, 'Array should contain exactly 3 transaction status entries');
        $this->assertArrayHasKey('PENDING', $enumValues);
        $this->assertArrayHasKey('CONFIRMED', $enumValues);
        $this->assertArrayHasKey('REJECTED', $enumValues);

        // 验证值的类型和内容正确性：业务状态的数值表示
        $this->assertSame(1, $enumValues['PENDING']);
        $this->assertSame(2, $enumValues['CONFIRMED']);
        $this->assertSame(3, $enumValues['REJECTED']);

        // 验证状态值的业务逻辑顺序：按处理流程递增
        $sortedByValue = $enumValues;
        asort($sortedByValue);
        $expectedOrder = ['PENDING' => 1, 'CONFIRMED' => 2, 'REJECTED' => 3];
        $this->assertSame($expectedOrder, $sortedByValue, 'Status values should follow processing order');

        // 验证状态值范围和间隔的业务合理性
        $values = array_values($enumValues);
        $this->assertSame([1, 2, 3], $values, 'Status values should be consecutive integers starting from 1');

        // 确保没有多余的键
        $expectedKeys = ['PENDING', 'CONFIRMED', 'REJECTED'];
        $this->assertSame($expectedKeys, array_keys($enumValues), 'Array keys should match expected status names');
    }
}
