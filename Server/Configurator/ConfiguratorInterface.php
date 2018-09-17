<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Server\Configurator;

use Swoole\Http\Server;

interface ConfiguratorInterface
{
    /**
     * @param Server $server
     */
    public function configure(Server $server): void;
}
