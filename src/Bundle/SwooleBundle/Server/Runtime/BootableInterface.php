<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Server\Runtime;

interface BootableInterface
{
    /**
     * Overrides configuration at runtime.
     *
     * @param array $runtimeConfiguration
     */
    public function boot(array $runtimeConfiguration = []): void;
}
