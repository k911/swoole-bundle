<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Server;

use Swoole\Http\Server;

class HttpServerFactory
{
    public function make(HttpServerConfiguration $configuration): Server
    {
        return new Server(
            $configuration->getHost(),
            $configuration->getPort(),
            $configuration->getSwooleRunningMode(),
            $configuration->getSwooleSocketType()
        );
    }
}
