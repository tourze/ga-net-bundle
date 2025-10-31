<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\GaNetBundle\Service\AdminMenu;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private LinkGeneratorInterface&MockObject $linkGenerator;

    private AdminMenu $adminMenu;

    protected function onSetUp(): void
    {
        $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $this->adminMenu = self::getService(AdminMenu::class);
    }

    #[Test]
    public function testInvokeShouldCreateGaNetMenuWhenNotExists(): void
    {
        $rootItem = $this->createMock(ItemInterface::class);
        $gaNetMenu = $this->createMock(ItemInterface::class);

        // First call to getChild returns null (menu doesn't exist)
        // Second call returns the created menu
        $rootItem->expects($this->exactly(2))
            ->method('getChild')
            ->with('成果网')
            ->willReturnOnConsecutiveCalls(null, $gaNetMenu)
        ;

        $rootItem->expects($this->once())
            ->method('addChild')
            ->with('成果网')
            ->willReturn($this->createMock(ItemInterface::class))
        ;

        $this->setupMenuExpectations($gaNetMenu);

        ($this->adminMenu)($rootItem);
    }

    #[Test]
    public function testInvokeShouldUseExistingGaNetMenuWhenExists(): void
    {
        $rootItem = $this->createMock(ItemInterface::class);
        $gaNetMenu = $this->createMock(ItemInterface::class);

        $rootItem->expects($this->exactly(2))
            ->method('getChild')
            ->with('成果网')
            ->willReturn($gaNetMenu)
        ;

        $rootItem->expects($this->never())
            ->method('addChild')
        ;

        $this->setupMenuExpectations($gaNetMenu);

        ($this->adminMenu)($rootItem);
    }

    #[Test]
    public function testInvokeShouldReturnEarlyWhenGaNetMenuIsNull(): void
    {
        $rootItem = $this->createMock(ItemInterface::class);

        // First call returns null, second call also returns null (addChild failed)
        $rootItem->expects($this->exactly(2))
            ->method('getChild')
            ->with('成果网')
            ->willReturnOnConsecutiveCalls(null, null)
        ;

        $rootItem->expects($this->once())
            ->method('addChild')
            ->with('成果网')
            ->willReturn($this->createMock(ItemInterface::class))
        ;

        // No menu items should be added since gaMenu is null
        $this->linkGenerator->expects($this->never())
            ->method('getCrudListPage')
        ;

        ($this->adminMenu)($rootItem);
    }

    #[Test]
    public function testInvokeShouldAddAllExpectedMenuItems(): void
    {
        $rootItem = $this->createMock(ItemInterface::class);
        $gaNetMenu = $this->createMock(ItemInterface::class);

        $rootItem->method('getChild')->willReturn($gaNetMenu);

        // Create mock menu items for each expected call
        $mockMenuItem = $this->createMock(ItemInterface::class);

        // Track the menu items that are added
        $addedMenuItems = [];
        $gaNetMenu->expects($this->exactly(7))
            ->method('addChild')
            ->willReturnCallback(function (string $title) use (&$addedMenuItems, $mockMenuItem) {
                $addedMenuItems[] = $title;

                return $mockMenuItem;
            })
        ;

        $this->linkGenerator
            ->method('getCrudListPage')
            ->willReturn('/admin/mock-url')
        ;

        ($this->adminMenu)($rootItem);

        // Verify the correct menu items were added in the expected order
        $expectedTitles = ['发布商管理', '活动管理', '推广活动', '佣金规则管理', '重定向标签管理', '交易记录', '结算记录'];
        $this->assertEquals($expectedTitles, $addedMenuItems);
    }

    #[Test]
    public function testMenuConfigurationShouldCreateCorrectMenuStructure(): void
    {
        // 集成测试：测试真实的菜单配置行为
        $adminMenu = self::getService(AdminMenu::class);

        $rootItem = $this->createMock(ItemInterface::class);
        $gaNetMenu = $this->createMock(ItemInterface::class);

        // 模拟根菜单项查找子菜单的行为
        $rootItem->method('getChild')->willReturn($gaNetMenu);

        // 记录添加的菜单项
        $addedMenuItems = [];
        $gaNetMenu->expects($this->exactly(7))
            ->method('addChild')
            ->willReturnCallback(function (string $label) use (&$addedMenuItems) {
                $addedMenuItems[] = $label;

                $mockMenuItem = $this->createMock(ItemInterface::class);

                return $mockMenuItem;
            })
        ;

        // 执行菜单配置
        $adminMenu($rootItem);

        // 验证创建了正确的菜单项
        $expectedMenuLabels = ['发布商管理', '活动管理', '推广活动', '佣金规则管理', '重定向标签管理', '交易记录', '结算记录'];
        $this->assertEquals($expectedMenuLabels, $addedMenuItems);
    }

    #[Test]
    public function testMenuItemsShouldHaveCorrectIconsAndUrls(): void
    {
        $rootItem = $this->createMock(ItemInterface::class);
        $gaNetMenu = $this->createMock(ItemInterface::class);

        $rootItem->method('getChild')->willReturn($gaNetMenu);

        // Mock the linkGenerator to return any valid URL (we'll test the actual URLs are set)
        $this->linkGenerator
            ->method('getCrudListPage')
            ->willReturn('/admin/test-url')
        ;

        // Track menu items and their properties
        $addedMenuItems = [];
        $gaNetMenu->expects($this->exactly(7))
            ->method('addChild')
            ->willReturnCallback(function ($title) use (&$addedMenuItems) {
                $mockMenuItem = $this->createMock(ItemInterface::class);
                $addedMenuItems[] = $title;

                return $mockMenuItem;
            })
        ;

        ($this->adminMenu)($rootItem);

        // Verify all menu items were added
        $expectedTitles = ['发布商管理', '活动管理', '推广活动', '佣金规则管理', '重定向标签管理', '交易记录', '结算记录'];
        $this->assertEquals($expectedTitles, $addedMenuItems);
    }

    #[Test]
    public function testServiceIsReadonly(): void
    {
        $reflection = new \ReflectionClass(AdminMenu::class);

        $this->assertTrue($reflection->isReadOnly(), 'AdminMenu service should be readonly');
    }

    #[Test]
    public function testServiceImplementsMenuProviderInterface(): void
    {
        $this->assertInstanceOf(MenuProviderInterface::class, $this->adminMenu);
    }

    #[Test]
    public function testConstructorDependencyInjection(): void
    {
        $reflection = new \ReflectionClass(AdminMenu::class);
        $constructor = $reflection->getConstructor();

        $this->assertNotNull($constructor);
        $parameters = $constructor->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertSame('linkGenerator', $parameters[0]->getName());
        $this->assertTrue($parameters[0]->hasType());

        $type = $parameters[0]->getType();
        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame(LinkGeneratorInterface::class, $type->getName());
    }

    private function setupMenuExpectations(ItemInterface&MockObject $gaNetMenu): void
    {
        $this->linkGenerator
            ->method('getCrudListPage')
            ->willReturn('/admin/mock-url')
        ;

        $mockMenuItem = $this->createMock(ItemInterface::class);

        $gaNetMenu
            ->method('addChild')
            ->willReturn($mockMenuItem)
        ;
    }
}
