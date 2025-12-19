<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\GaNetBundle\GaNetBundle;

/**
 * @internal
 */
#[CoversClass(GaNetBundle::class)]
class BasicTest extends TestCase
{
    public function testBundleCanBeInstantiated(): void
    {
        $bundle = new GaNetBundle();
        $this->assertInstanceOf(GaNetBundle::class, $bundle);
        $this->assertSame('GaNetBundle', $bundle->getName());
    }
}