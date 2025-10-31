<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\GaNetBundle\Exception\GaNetApiException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(GaNetApiException::class)]
final class GaNetApiExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCreation(): void
    {
        $exception = new GaNetApiException('Test error message');

        $this->assertSame('Test error message', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testExceptionWithCode(): void
    {
        $exception = new GaNetApiException('Test error message', 500);

        $this->assertSame('Test error message', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \RuntimeException('Previous error');
        $exception = new GaNetApiException('Test error message', 0, $previous);

        $this->assertSame('Test error message', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
