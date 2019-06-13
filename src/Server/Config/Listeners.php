<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Config;

use Assert\Assertion;
use K911\Swoole\Server\ServerFactory;

final class Listeners
{
    private $mainSocket;
    private $mainSocketHostPort;
    private $listenersMappedByHostPorts;

    public function __construct(Socket $mainSocket, Listener ...$additionalListeners)
    {
        $this->mainSocket = $mainSocket;
        $this->mainSocketHostPort = $mainSocket->hostPort();
        $this->listenersMappedByHostPorts = [];
        $this->addListeners(...$additionalListeners);
    }

    public function addListeners(Listener ...$listeners): void
    {
        foreach ($listeners as $listener) {
            $listenerHostPort = $listener->socket()->hostPort();
            Assertion::notEq($this->mainSocketHostPort, $listenerHostPort, 'Host and port combination "%s" has already been registered as main server listener');
            Assertion::keyNotExists($this->listenersMappedByHostPorts, $listenerHostPort, 'Host and port combination "%s" has already been registered');
            $this->listenersMappedByHostPorts[$listenerHostPort] = $listener;
        }
    }

    public function changeMainSocket(Socket $socket): void
    {
        Assertion::keyNotExists($this->listenersMappedByHostPorts, $socket->hostPort(), 'Host and port combination "%s" cannot be used as main server listener because it has already been registered as regular listener');
        $this->mainSocket = $socket;
    }

    public function mainSocket(): Socket
    {
        return $this->mainSocket;
    }

    /**
     * @return iterable<Listener>
     */
    public function get(): iterable
    {
        foreach ($this->listenersMappedByHostPorts as $hostPort => $listener) {
            yield $listener;
        }
    }

    public function portsInferredServerTypes(): iterable
    {
        /** @var Listener $listener */
        foreach ($this->get() as $listener) {
            yield $listener->eventsCallbacks()->inferServerType();
        }
    }

    public function inferServerType(): string
    {
        return ServerFactory::inferType($this->portsInferredServerTypes());
    }
}
