<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Api;

use Assert\Assertion;
use K911\Swoole\Client\Http;
use K911\Swoole\Client\HttpClient;
use K911\Swoole\Server\Config\Sockets;
use Swoole\Coroutine\Http\Client;

final class ApiServerClient implements ApiServerInterface
{
    /**
     * @var HttpClient|null
     */
    private $client;
    private $sockets;

    public function __construct(Sockets $sockets)
    {
        $this->sockets = $sockets;
    }

    /**
     * Get Swoole HTTP Server status.
     *
     * @return array
     */
    public function status(): array
    {
        return $this->getClient()->send('/api/server')['response']['body'];
    }

    /**
     * Shutdown Swoole HTTP Server.
     */
    public function shutdown(): void
    {
        $this->getClient()->send('/api/server', Http::METHOD_DELETE);
    }

    /**
     * Reload Swoole HTTP Server workers.
     */
    public function reload(): void
    {
        $this->getClient()->send('/api/server', Http::METHOD_PATCH);
    }

    /**
     * Get Swoole HTTP Server metrics.
     *
     * @return array
     */
    public function metrics(): array
    {
        return $this->getClient()->send('/api/server/metrics')['response']['body'];
    }

    private function getClient(): HttpClient
    {
        if (!$this->client instanceof HttpClient) {
            $this->client = $this->newClient($this->sockets);
        }

        return $this->client;
    }

    private function newClient(Sockets $sockets): HttpClient
    {
        Assertion::true($sockets->hasApiSocket(), 'Swoole HTTP Server is not configured properly. To access API trough HTTP interface, you must enable and provide proper address of configured API Server.');
        $apiSocket = $sockets->getApiSocket();

        $swooleClient = new Client($apiSocket->host(), $apiSocket->port(), $apiSocket->ssl());

        return new HttpClient($swooleClient);
    }
}
