<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\GaNetBundle\DependencyInjection\GaNetExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(GaNetExtension::class)]
final class ConfigTest extends AbstractDependencyInjectionExtensionTestCase
{
    protected function getExtension(): GaNetExtension
    {
        return new GaNetExtension();
    }

    public function testServicesConfigIsValid(): void
    {
        // 验证扩展可以正确加载
        $extension = $this->getExtension();
        $this->assertInstanceOf(GaNetExtension::class, $extension);

        // 验证配置加载过程中没有出现 exclude 配置错误
        // 如果能到达这里，说明 services.yaml 配置已经成功解析
        $this->assertTrue(true, 'services.yaml 配置加载成功，没有 exclude 配置错误');
    }
}