<?php

declare(strict_types=1);

namespace K911\Swoole\Server;

interface ServerFactoryInterface
{
    /**
     * Make configured Server instance.
     */
    public function make(): ServerInterface;
}
