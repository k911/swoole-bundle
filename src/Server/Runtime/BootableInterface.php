<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Runtime;

interface BootableInterface
{
    /**
     * Overrides configuration at runtime.
     *
     * @param array $runtimeConfiguration
     */
    public function boot(array $runtimeConfiguration = []): void;
}
