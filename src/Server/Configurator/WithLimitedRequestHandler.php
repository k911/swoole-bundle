<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Configurator;

use K911\Swoole\Server\RequestHandler\LimitedRequestHandler;
use Swoole\Http\Server;

final class WithLimitedRequestHandler implements ConfiguratorInterface
{
    private $decorated;
    private $requestHandler;

    public function __construct(ConfiguratorInterface $decorated, LimitedRequestHandler $requestHandler)
    {
        $this->decorated = $decorated;
        $this->requestHandler = $requestHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Server $server): void
    {
        $this->decorated->configure($server);

        $server->on('request', [$this->requestHandler, 'handle']);
    }
}
