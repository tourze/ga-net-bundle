<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\GaNetBundle\Enum\Currency;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(Currency::class)]
final class CurrencyTest extends AbstractEnumTestCase
{
    #[Test]
    public function testEnumValuesShouldBeCorrect(): void
    {
        $this->assertSame('CNY', Currency::CNY->value);
        $this->assertSame('USD', Currency::USD->value);
        $this->assertSame('EUR', Currency::EUR->value);
        $this->assertSame('GBP', Currency::GBP->value);
    }

    #[Test]
    public function testGetLabelShouldReturnCorrectLabels(): void
    {
        $this->assertSame('人民币', Currency::CNY->getLabel());
        $this->assertSame('美元', Currency::USD->getLabel());
        $this->assertSame('欧元', Currency::EUR->getLabel());
        $this->assertSame('英镑', Currency::GBP->getLabel());
    }

    #[Test]
    public function testGetSymbolShouldReturnCorrectSymbols(): void
    {
        $this->assertSame('¥', Currency::CNY->getSymbol());
        $this->assertSame('$', Currency::USD->getSymbol());
        $this->assertSame('€', Currency::EUR->getSymbol());
        $this->assertSame('£', Currency::GBP->getSymbol());
    }

    #[Test]
    public function testFromValueShouldCreateCorrectEnumInstance(): void
    {
        $this->assertSame(Currency::CNY, Currency::from('CNY'));
        $this->assertSame(Currency::USD, Currency::from('USD'));
        $this->assertSame(Currency::EUR, Currency::from('EUR'));
        $this->assertSame(Currency::GBP, Currency::from('GBP'));
    }

    #[Test]
    public function testTryFromShouldReturnNullForInvalidValue(): void
    {
        $this->assertNull(Currency::tryFrom('JPY'));
        $this->assertNull(Currency::tryFrom('INVALID'));
        $this->assertNull(Currency::tryFrom(''));
        $this->assertNull(Currency::tryFrom('cny')); // Case sensitive
    }

    #[Test]
    public function testTryFromShouldReturnCorrectEnumForValidValue(): void
    {
        $this->assertSame(Currency::CNY, Currency::tryFrom('CNY'));
        $this->assertSame(Currency::USD, Currency::tryFrom('USD'));
        $this->assertSame(Currency::EUR, Currency::tryFrom('EUR'));
        $this->assertSame(Currency::GBP, Currency::tryFrom('GBP'));
    }

    #[Test]
    public function testGetCasesShouldReturnAllEnumCases(): void
    {
        $cases = Currency::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(Currency::CNY, $cases);
        $this->assertContains(Currency::USD, $cases);
        $this->assertContains(Currency::EUR, $cases);
        $this->assertContains(Currency::GBP, $cases);
    }

    #[Test]
    public function testEnumImplementsExpectedInterfaces(): void
    {
        $enum = Currency::CNY;

        $this->assertInstanceOf(Labelable::class, $enum);
        $this->assertInstanceOf(Itemable::class, $enum);
        $this->assertInstanceOf(Selectable::class, $enum);
    }

    #[Test]
    public function testCurrencyFormattingLogicShouldWorkCorrectly(): void
    {
        // 测试实际业务场景：格式化价格显示
        $amount = '100.50';

        $cnyFormatted = Currency::CNY->getSymbol() . $amount;
        $this->assertSame('¥100.50', $cnyFormatted);

        $usdFormatted = Currency::USD->getSymbol() . $amount;
        $this->assertSame('$100.50', $usdFormatted);

        $eurFormatted = Currency::EUR->getSymbol() . $amount;
        $this->assertSame('€100.50', $eurFormatted);

        $gbpFormatted = Currency::GBP->getSymbol() . $amount;
        $this->assertSame('£100.50', $gbpFormatted);
    }

    #[Test]
    public function testAllCurrenciesHaveUniqueValuesAndSymbols(): void
    {
        $allCurrencies = Currency::cases();

        // 验证每个货币都有唯一的值、标签和符号
        $values = array_map(fn ($currency) => $currency->value, $allCurrencies);
        $labels = array_map(fn ($currency) => $currency->getLabel(), $allCurrencies);
        $symbols = array_map(fn ($currency) => $currency->getSymbol(), $allCurrencies);

        $this->assertSame(count($values), count(array_unique($values)), 'All currency values should be unique');
        $this->assertSame(count($labels), count(array_unique($labels)), 'All currency labels should be unique');
        $this->assertSame(count($symbols), count(array_unique($symbols)), 'All currency symbols should be unique');
    }

    #[Test]
    public function testCurrencyValuesShouldFollowISO4217Standard(): void
    {
        // 验证货币代码符合ISO 4217标准
        $isoCurrencies = ['CNY', 'USD', 'EUR', 'GBP'];

        foreach (Currency::cases() as $currency) {
            $this->assertContains($currency->value, $isoCurrencies);
            $this->assertSame(3, strlen($currency->value), 'Currency codes should be 3 characters long');
            $this->assertSame(strtoupper($currency->value), $currency->value, 'Currency codes should be uppercase');
        }
    }

    #[Test]
    public function testCurrencyBusinessLogicIntegration(): void
    {
        // 测试在实际业务中的使用场景
        $currency = Currency::CNY;

        // 模拟数据库存储和读取
        $storedValue = $currency->value;
        $retrievedCurrency = Currency::from($storedValue);

        $this->assertSame($currency, $retrievedCurrency);
        $this->assertSame('人民币', $retrievedCurrency->getLabel());
        $this->assertSame('¥', $retrievedCurrency->getSymbol());
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
        $array = $enumValues;

        // 测试实际的业务逻辑：数组结构的正确性和完整性
        $this->assertCount(4, $array, 'Array should contain exactly 4 currency entries');
        $this->assertArrayHasKey('CNY', $array);
        $this->assertArrayHasKey('USD', $array);
        $this->assertArrayHasKey('EUR', $array);
        $this->assertArrayHasKey('GBP', $array);

        // 验证值的类型和内容正确性
        $this->assertSame('CNY', $array['CNY']);
        $this->assertSame('USD', $array['USD']);
        $this->assertSame('EUR', $array['EUR']);
        $this->assertSame('GBP', $array['GBP']);

        // 确保没有多余的键
        $expectedKeys = ['CNY', 'USD', 'EUR', 'GBP'];
        $this->assertSame($expectedKeys, array_keys($array), 'Array keys should match expected currency codes');
    }
}
