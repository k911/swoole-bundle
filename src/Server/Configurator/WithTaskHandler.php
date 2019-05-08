<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Configurator;

use K911\Swoole\Server\HttpServerConfiguration;
use K911\Swoole\Server\TaskHandler\TaskHandlerInterface;
use Swoole\Http\Server;

final class WithTaskHandler implements ConfiguratorInterface
{
    private $handler;
    private $configuration;

    public function __construct(TaskHandlerInterface $handler, HttpServerConfiguration $configuration)
    {
        $this->handler = $handler;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Server $server): void
    {
        if ($this->configuration->getTaskWorkerCount() > 0) {
            $server->on('task', [$this->handler, 'handle']);
        }
    }
}
