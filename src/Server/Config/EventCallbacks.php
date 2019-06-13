<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Config;

use Assert\Assertion;
use Closure;
use K911\Swoole\Server\LifecycleHandler\ServerManagerStartHandlerInterface;
use K911\Swoole\Server\LifecycleHandler\ServerManagerStopHandlerInterface;
use K911\Swoole\Server\LifecycleHandler\ServerShutdownHandlerInterface;
use K911\Swoole\Server\LifecycleHandler\ServerStartHandlerInterface;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use K911\Swoole\Server\Server;
use K911\Swoole\Server\WorkerHandler\WorkerStartHandlerInterface;

final class EventCallbacks
{
    // https://github.com/swoole/swoole-src/blob/master/swoole_server.cc#L74
    // https://github.com/swoole/swoole-src/blob/master/swoole_server.h#L24
    // https://github.com/swoole/swoole-src/blob/master/swoole_server_port.cc
    public const EVENT_SERVER_START = 'start';
    public const EVENT_SERVER_SHUTDOWN = 'shutdown';
    public const EVENT_MANAGER_START = 'managerstart';
    public const EVENT_MANAGER_STOP = 'managerstop';
    public const EVENT_WORKER_START = 'workerstart';
    public const EVENT_WORKER_STOP = 'workerstop';
    public const EVENT_WORKER_EXIT = 'workerexit';
    public const EVENT_WORKER_ERROR = 'workererror';
    public const EVENT_BEFORE_RELOAD = 'beforereload';
    public const EVENT_AFTER_RELOAD = 'afterreload';
    public const EVENT_CONNECT = 'connect';
    public const EVENT_RECEIVE = 'receive';
    // UDP
    public const EVENT_PACKET = 'packet';
    public const EVENT_CLOSE = 'close';
    public const EVENT_TASK = 'task';
    public const EVENT_TASK_FINISH = 'finish';
    public const EVENT_PIPE_MESSAGE = 'pipemessage';
    public const EVENT_HTTP_REQUEST = 'request';
    public const EVENT_WEBSOCKET_OPEN = 'open';
    public const EVENT_WEBSOCKET_HANDSHAKE = 'handshake';
    public const EVENT_WEBSOCKET_MESSAGE = 'message';

    /**
     * Possible events to register callbacks on swoole server instances.
     *
     * @see https://wiki.swoole.com/wiki/page/41.html
     * @see \Swoole\Server
     * @see https://wiki.swoole.com/wiki/page/41.html
     * @see \Swoole\Http\Server
     * @see https://wiki.swoole.com/wiki/page/400.html
     * @see \Swoole\WebSocket\Server
     */
    public const SERVER_EVENTS_BY_TYPE = [
        self::EVENT_SERVER_START => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_SERVER_SHUTDOWN => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_WORKER_START => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_WORKER_STOP => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_WORKER_EXIT => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_CONNECT => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_RECEIVE => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_PACKET => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_CLOSE => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_TASK => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_TASK_FINISH => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_PIPE_MESSAGE => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_WORKER_ERROR => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_MANAGER_START => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_MANAGER_STOP => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_BEFORE_RELOAD => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_AFTER_RELOAD => Server::SERVER_TYPE_TRANSPORT,
        self::EVENT_HTTP_REQUEST => Server::SERVER_TYPE_HTTP,
        self::EVENT_WEBSOCKET_OPEN => Server::SERVER_TYPE_WEBSOCKET,
        self::EVENT_WEBSOCKET_HANDSHAKE => Server::SERVER_TYPE_WEBSOCKET,
        self::EVENT_WEBSOCKET_MESSAGE => Server::SERVER_TYPE_WEBSOCKET,
    ];

    private $registeredEvents;

    public function __construct(array $registeredEvents = [])
    {
        $this->registeredEvents = $registeredEvents;
        // TODO: VALIDATE INPUT
    }

    public function register(string $event, callable $eventCallback, int $priority = 100): void
    {
        $event = \mb_strtolower($event);
        Assertion::keyExists(self::SERVER_EVENTS_BY_TYPE, $event, 'Event name "%s" is invalid.');

        $callbackPriorityPair = [$eventCallback, $priority];
        if (!\array_key_exists($event, $this->registeredEvents)) {
            $this->registeredEvents[$event] = [$callbackPriorityPair];

            return;
        }

        $this->registeredEvents[$event][] = $callbackPriorityPair;
    }

    public function inferServerType(): string
    {
        if (\array_key_exists(self::EVENT_WEBSOCKET_MESSAGE, $this->registeredEvents)) {
            return Server::SERVER_TYPE_WEBSOCKET;
        }

        if (\array_key_exists(self::EVENT_HTTP_REQUEST, $this->registeredEvents)) {
            return Server::SERVER_TYPE_HTTP;
        }

        return Server::SERVER_TYPE_TRANSPORT;
    }

    /**
     * @return \Generator&iterable<string, callable>
     */
    public function get(string $serverType, string $runningMode, bool $listener): iterable
    {
        $events = $this->filterEvents($this->registeredEvents, $serverType, $runningMode, $listener);

        foreach ($events as $eventName => $callbackPriorityPairGroup) {
            $count = \count($callbackPriorityPairGroup);

            switch ($count) {
                // No event callbacks defined
                case 0:
                    // noop
                    break;
                // Single event callback defined
                case 1:
                    yield $eventName => $callbackPriorityPairGroup[0][0];

                    break;
                // Multiple event callbacks defined
                default:
                    yield $eventName => $this->eventCallbacksAggregator($callbackPriorityPairGroup);

                    break;
            }
        }
    }

    public function registerHttpRequestHandler(RequestHandlerInterface $requestHandler, int $priority = 100): void
    {
        $this->register(self::EVENT_HTTP_REQUEST, [$requestHandler, 'handle'], $priority);
    }

    public function registerServerStartHandler(ServerStartHandlerInterface $serverStartHandler, int $priority = 100): void
    {
        $this->register(self::EVENT_SERVER_START, [$serverStartHandler, 'handle'], $priority);
    }

    public function registerServerShutdownHandler(ServerShutdownHandlerInterface $serverShutdownHandler, int $priority = 100): void
    {
        $this->register(self::EVENT_SERVER_SHUTDOWN, [$serverShutdownHandler, 'handle'], $priority);
    }

    public function registerServerManagerStartHandler(ServerManagerStartHandlerInterface $serverManagerStartHandler, int $priority = 100): void
    {
        $this->register(self::EVENT_MANAGER_START, [$serverManagerStartHandler, 'handle'], $priority);
    }

    public function registerServerManagerStopHandler(ServerManagerStopHandlerInterface $serverManagerStopHandler, int $priority = 100): void
    {
        $this->register(self::EVENT_MANAGER_STOP, [$serverManagerStopHandler, 'handle'], $priority);
    }

    public function registerWorkerStartHandler(WorkerStartHandlerInterface $workerStartHandler, int $priority = 100): void
    {
        $this->register(self::EVENT_WORKER_START, [$workerStartHandler, 'handle'], $priority);
    }

    private function filterEvents(array $events, string $serverType, string $serverRunningMode, bool $listener): array
    {
        /** @var string $swooleVersion */
        $swooleVersion = \swoole_version();

        // Events "beforeReload" and "afterReload" are supported since Swoole 4.5.x
        if ((int) $swooleVersion[2] < 5) {
            unset($events[self::EVENT_AFTER_RELOAD], $events[self::EVENT_BEFORE_RELOAD]);
        }

        // Event "onStart" has been deprecated by swoole team
        if (ServerConfig::RUNNING_MODE_REACTOR === $serverRunningMode) {
            // TODO: warning?
            unset($events[self::EVENT_SERVER_START]);
        }

        if (Server::SERVER_TYPE_WEBSOCKET !== $serverType) {
            unset(
                $events[self::EVENT_WEBSOCKET_OPEN],
                $events[self::EVENT_WEBSOCKET_MESSAGE],
                $events[self::EVENT_WEBSOCKET_HANDSHAKE],
            );
        }

        if (Server::SERVER_TYPE_TRANSPORT === $serverType) {
            unset($events[self::EVENT_HTTP_REQUEST]);
        }

        // Server lifecycle events cannot be set on listeners
        if ($listener) {
            unset(
                $events[self::EVENT_SERVER_START],
                $events[self::EVENT_MANAGER_START],
                $events[self::EVENT_WORKER_START],
                $events[self::EVENT_AFTER_RELOAD],
                $events[self::EVENT_BEFORE_RELOAD],
                $events[self::EVENT_MANAGER_STOP],
                $events[self::EVENT_WORKER_STOP],
                $events[self::EVENT_WORKER_EXIT],
                $events[self::EVENT_WORKER_ERROR],
                $events[self::EVENT_SERVER_SHUTDOWN],
            );
        }

        return $events;
    }

    /**
     * Return aggregate event callback which iterates by all event callbacks in group sorted by priority.
     */
    private function eventCallbacksAggregator(array $callbackPriorityPairGroup): Closure
    {
        // Sort event callbacks by priority (descending)
        \usort($callbackPriorityPairGroup, function (array $callbackPriorityPairOne, array $callbackPriorityPairTwo) {
            return $callbackPriorityPairOne[1] <=> $callbackPriorityPairTwo[1];
        });

        /** @var callable[] $sortedEventCallbacksByPriority */
        $sortedEventCallbacksByPriority = \array_map(function (array $callbackPriorityPair): callable {
            return $callbackPriorityPair[0];
        }, $callbackPriorityPairGroup);

        return function (...$args) use ($sortedEventCallbacksByPriority): void {
            foreach ($sortedEventCallbacksByPriority as $callback) {
                $callback(...$args);
            }
        };
    }
}
