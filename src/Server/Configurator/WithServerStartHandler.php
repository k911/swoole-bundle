<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Configurator;

use K911\Swoole\Server\LifecycleHandler\ServerStartHandlerInterface;
use Swoole\Http\Server;

final class WithServerStartHandler implements ConfiguratorInterface
{
    private $handler;
    private $decorated;

    public function __construct(ConfiguratorInterface $decorated, ServerStartHandlerInterface $handler)
    {
        $this->handler = $handler;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Server $server): void
    {
        $this->decorated->configure($server);

        $server->on('start', [$this->handler, 'handle']);
    }
}
