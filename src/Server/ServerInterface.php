<?php

declare(strict_types=1);

namespace K911\Swoole\Server;

interface ServerInterface
{
    public function start(): bool;

    public function shutdown(): void;

    public function reload(): void;

    public function metrics(): array;

    /**
     * @param mixed $data
     */
    public function dispatchTask($data): void;

    public function running(): bool;

    public function info(): array;
}
