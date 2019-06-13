<?php

declare(strict_types=1);

namespace K911\Swoole\Server;

use Assert\Assertion;
use K911\Swoole\Server\Config\EventCallbacks;
use K911\Swoole\Server\Config\Listener;
use K911\Swoole\Server\Config\Listeners;
use K911\Swoole\Server\Config\ServerConfig;
use Swoole\Http\Server as SwooleHttpServer;
use Swoole\Server as SwooleServer;
use Swoole\WebSocket\Server as SwooleWebsocketServer;

final class ServerFactory implements ServerFactoryInterface
{
    public const SERVER_CLASS_BY_TYPE = [
        Server::SERVER_TYPE_WEBSOCKET => SwooleWebsocketServer::class,
        Server::SERVER_TYPE_HTTP => SwooleHttpServer::class,
        Server::SERVER_TYPE_TRANSPORT => SwooleServer::class,
    ];

    private $config;
    private $listeners;
    private $callbacks;

    public function __construct(ServerConfig $config, Listeners $listeners, EventCallbacks $callbacks)
    {
        $this->config = $config;
        $this->listeners = $listeners;
        $this->callbacks = $callbacks;
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    public function make(): ServerInterface
    {
        $serverType = $this->inferredType();
        $serverClass = self::SERVER_CLASS_BY_TYPE[$serverType];
        $swooleServer = $this->createSwooleServerInstance($serverClass);

        $this->configureSwooleServer($swooleServer, $serverType);

        // TODO:
        // $this->config->lock();
        // $this->listeners->lock();
        // $this->callbacks->lock();

        return new Server($swooleServer, $this->config, $this->listeners, $this->callbacks);
    }

    public function inferredType(): string
    {
        return self::inferType([$this->callbacks->inferServerType(), $this->listeners->inferServerType()]);
    }

    public static function inferType(iterable $types): string
    {
        $inferredType = Server::SERVER_TYPE_TRANSPORT;
        foreach ($types as $type) {
            if (Server::SERVER_TYPE_WEBSOCKET === $type) {
                return Server::SERVER_TYPE_WEBSOCKET;
            }

            if (Server::SERVER_TYPE_HTTP === $type) {
                $inferredType = Server::SERVER_TYPE_HTTP;
            }
        }

        return $inferredType;
    }

    /**
     * @param class-string $serverClass
     *
     * @return \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server
     */
    private function createSwooleServerInstance(string $serverClass): object
    {
        $mainSocket = $this->listeners->mainSocket();

        return new $serverClass(
            $mainSocket->host(),
            $mainSocket->port(),
            $this->config->swooleRunningMode(),
            $mainSocket->type(),
        );
    }

    /**
     * @param \Swoole\Http\Server|\Swoole\Server|\Swoole\WebSocket\Server $swooleServer
     *
     * @throws \Assert\AssertionFailedException
     */
    private function configureSwooleServer($swooleServer, string $serverType): void
    {
        $runningMode = $this->config->runningMode();
        $swooleServer->set($this->config->swooleConfig());

        $mainSocket = $this->listeners->mainSocket();
        if (0 === $mainSocket->port()) {
            $this->listeners->changeMainSocket($mainSocket->withPort($swooleServer->port));
        }

        /** @var callable[] $callbacks */
        $callbacks = $this->callbacks->get($serverType, $runningMode, false);

        foreach ($callbacks as $eventName => $callback) {
            Assertion::isCallable($callback, \sprintf('Callback for event "%s" is not a callable. Actual type: %s', $eventName, \gettype($callback)));
            $swooleServer->on($eventName, $callback);
        }

        /** @var Listener[] $listeners */
        $listeners = $this->listeners->get();
        foreach ($listeners as $listener) {
            $socket = $listener->socket();
            /** @var \Swoole\Server\Port $port */
            $port = $swooleServer->listen($socket->host(), $socket->port(), $socket->type());
            $port->set($listener->config()->all());
            foreach ($listener->eventsCallbacks()->get($serverType, $runningMode, true) as $eventName => $callback) {
                Assertion::isCallable($callback, \sprintf('Callback for event "%s" is not a callable. Actual type: %s', $eventName, \gettype($callback)));
                $port->on($eventName, $callback);
            }
        }
    }
}
