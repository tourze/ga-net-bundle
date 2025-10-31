<?php

declare(strict_types=1);

namespace Tourze\GaNetBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class GaNetExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
