<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Runtime\HMR;

use Swoole\Server;

interface HotModuleReloaderInterface
{
    /**
     * Reload HttpServer if changes in files were detected.
     *
     * @param Server $server
     */
    public function tick(Server $server): void;
}
