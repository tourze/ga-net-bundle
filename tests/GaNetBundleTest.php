<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\GaNetBundle\GaNetBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(GaNetBundle::class)]
#[RunTestsInSeparateProcesses]
final class GaNetBundleTest extends AbstractBundleTestCase
{
}
