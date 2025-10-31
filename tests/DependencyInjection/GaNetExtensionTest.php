<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\GaNetBundle\DependencyInjection\GaNetExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(GaNetExtension::class)]
final class GaNetExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testExtensionCreation(): void
    {
        $extension = new GaNetExtension();

        $this->assertSame('ga_net', $extension->getAlias());
    }

    public function testGetConfigDir(): void
    {
        $extension = new GaNetExtension();

        // 使用反射访问protected方法
        $reflection = new \ReflectionClass($extension);
        $method = $reflection->getMethod('getConfigDir');
        $method->setAccessible(true);
        $configDir = $method->invoke($extension);

        $this->assertIsString($configDir);
        $this->assertStringContainsString('Resources/config', $configDir);
        $this->assertStringContainsString('ga-net-bundle', $configDir);
    }
}
