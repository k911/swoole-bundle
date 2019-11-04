<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Config;

use Assert\Assertion;
use Generator;

final class Sockets
{
    private $serverSocket;
    private $additionalSockets;

    /**
     * @var null|Socket
     */
    private $apiSocket;

    public function __construct(Socket $serverSocket, ?Socket $apiSocket = null, Socket ...$additionalSockets)
    {
        $this->serverSocket = $serverSocket;
        $this->apiSocket = $apiSocket;
        $this->additionalSockets = $additionalSockets;
    }

    public function changeServerSocket(Socket $socket): void
    {
        $this->serverSocket = $socket;
    }

    public function getServerSocket(): Socket
    {
        return $this->serverSocket;
    }

    public function getApiSocket(): Socket
    {
        Assertion::isInstanceOf($this->apiSocket, Socket::class, 'API Socket is not defined.');

        return $this->apiSocket;
    }

    public function hasApiSocket(): bool
    {
        return $this->apiSocket instanceof Socket;
    }

    public function disableApiSocket(): void
    {
        $this->apiSocket = null;
    }

    public function changeApiSocket(Socket $socket): void
    {
        $this->apiSocket = $socket;
    }

    /**
     * Get sockets in order:
     * - first server socket
     * - next if defined api socket
     * - rest of sockets.
     */
    public function getAll(): Generator
    {
        yield $this->serverSocket;

        if ($this->hasApiSocket()) {
            yield $this->apiSocket;
        }

        yield from $this->additionalSockets;
    }
}
