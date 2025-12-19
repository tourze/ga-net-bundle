<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\Exception;

final class GaNetApiException extends \RuntimeException
{
    public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
