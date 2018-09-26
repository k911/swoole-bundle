<?php

declare(strict_types=1);

namespace K911\Swoole\Server;

use Assert\Assertion;
use K911\Swoole\Server\Config\Socket;
use K911\Swoole\Server\Configurator\ConfiguratorInterface;
use Swoole\Http\Server;

class HttpServerFactory
{
    private const SWOOLE_RUNNING_MODE = [
        'process' => SWOOLE_PROCESS,
        'reactor' => SWOOLE_BASE,
//        'thread' => SWOOLE_THREAD,
    ];

    private $configurator;

    public function __construct(ConfiguratorInterface $configurator)
    {
        $this->configurator = $configurator;
    }

    /**
     * @param Socket $socket
     * @param string $runningMode
     *
     * @return Server
     *
     * @see https://github.com/swoole/swoole-docs/blob/master/modules/swoole-server/methods/construct.md#parameter
     */
    public function make(Socket $socket, string $runningMode = 'process'): Server
    {
        Assertion::inArray($runningMode, \array_keys(self::SWOOLE_RUNNING_MODE));

        $server = new Server($socket->host(), $socket->port(), self::SWOOLE_RUNNING_MODE[$runningMode], $socket->type());

        $this->configurator->configure($server);

        return $server;
    }
}
