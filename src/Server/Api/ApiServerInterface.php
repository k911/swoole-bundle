<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Api;

interface ApiServerInterface
{
    /**
     * Get Swoole HTTP Server status.
     *
     * @return array
     */
    public function status(): array;

    /**
     * Shutdown Swoole HTTP Server.
     */
    public function shutdown(): void;

    /**
     * Reload Swoole HTTP Server workers.
     */
    public function reload(): void;

    /**
     * Get Swoole HTTP Server metrics.
     *
     * @return array
     */
    public function metrics(): array;
}
