<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Config;

final class Listener
{
    /**
     * @var Socket
     */
    private $socket;

    /**
     * @var ListenerConfig
     */
    private $config;

    /**
     * @var EventCallbacks
     */
    private $callbacks;

    /**
     * Port constructor.
     */
    public function __construct(Socket $socket, ListenerConfig $config, EventCallbacks $callbacks)
    {
        $this->socket = $socket;
        $this->config = $config;

        // TODO: Verify whether callbacks are proper for Listener type
        $this->callbacks = $callbacks;
    }

    public function socket(): Socket
    {
        return $this->socket;
    }

    public function config(): ListenerConfig
    {
        return $this->config;
    }

    public function eventsCallbacks(): EventCallbacks
    {
        return $this->callbacks;
    }
}
