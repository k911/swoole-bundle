<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\Configurator;

use K911\Swoole\Server\Configurator\ConfiguratorInterface;
use Swoole\Http\Server;

final class ConfiguratorSpy implements ConfiguratorInterface
{
    public $configured = false;

    /**
     * {@inheritdoc}
     */
    public function configure(Server $server): void
    {
        $this->configured = true;
    }
}
