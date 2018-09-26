<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Configurator;

use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use Swoole\Http\Server;

final class WithRequestHandler implements ConfiguratorInterface
{
    private $decorated;
    private $requestHandler;

    public function __construct(ConfiguratorInterface $decorated, RequestHandlerInterface $requestHandler)
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
