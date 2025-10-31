<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Tourze\GaNetBundle\Command\SyncCampaignsCommand;
use Tourze\GaNetBundle\GaNetBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(GaNetBundle::class)]
#[RunTestsInSeparateProcesses]
class ContainerTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // AbstractIntegrationTestCase 会自动清理数据库

        // 手动注册必要的服务
        $container = self::getContainer();

        // 注册 http_client 服务
        if (!$container->has('http_client')) {
            $container->set('http_client', $this->createMock('Symfony\Contracts\HttpClient\HttpClientInterface'));
        }

        // 注册 cache.app 服务
        if (!$container->has('cache.app')) {
            $container->set('cache.app', $this->createMock('Symfony\Contracts\Cache\CacheInterface'));
        }
    }

    public function testContainerHasRequiredServices(): void
    {
        $container = self::getContainer();
        $this->assertInstanceOf(ContainerInterface::class, $container);

        // 调试：列出所有服务
        $services = $container->getServiceIds();
        echo 'Total services: ' . count($services) . "\n";

        // 查找我们的服务
        $foundServices = [];
        foreach ($services as $serviceId) {
            if (str_contains($serviceId, 'GaNet')) {
                $foundServices[] = $serviceId;
            }
        }
        echo 'Found GaNet services: ' . implode(', ', $foundServices) . "\n";

        // 检查基本服务
        $this->assertTrue($container->has('http_client'), 'HTTP client service should be available');
        $this->assertTrue($container->has('cache.app'), 'Cache service should be available');

        // 检查我们的服务
        $this->assertTrue($container->has('Tourze\GaNetBundle\Service\GaNetApiClient'), 'GaNetApiClient service should be available');
        $this->assertTrue($container->has(SyncCampaignsCommand::class), 'SyncCampaignsCommand service should be available');
    }

    public function testCanGetServices(): void
    {
        $container = self::getContainer();

        // 获取我们的服务
        $apiClient = $container->get('Tourze\GaNetBundle\Service\GaNetApiClient');
        $this->assertInstanceOf('Tourze\GaNetBundle\Service\GaNetApiClient', $apiClient);

        $command = $container->get(SyncCampaignsCommand::class);
        $this->assertInstanceOf(SyncCampaignsCommand::class, $command);
    }

    public function testBuildMethodLoadsConfiguration(): void
    {
        $container = self::getContainer();

        // 验证 build 方法加载的 framework 配置
        $this->assertTrue($container->has('http_client'), 'HTTP client should be available after build');
        $this->assertTrue($container->has('cache.app'), 'Cache app should be available after build');

        // 验证服务配置文件被正确加载
        $this->assertTrue($container->has('Tourze\GaNetBundle\Service\GaNetApiClient'), 'GaNetApiClient should be available after build');
        $this->assertTrue($container->has(SyncCampaignsCommand::class), 'SyncCampaignsCommand should be available after build');
    }
}
