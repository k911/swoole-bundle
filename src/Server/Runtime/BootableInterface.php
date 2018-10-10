<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Runtime;

interface BootableInterface
{
    /**
     * Used to provide or override configuration at runtime.
     *
     * This method will be called directly before starting Swoole server.
     *
     * @param array $runtimeConfiguration
     */
    public function boot(array $runtimeConfiguration = []): void;
}
