<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Api;

use K911\Swoole\Client\Http;
use K911\Swoole\Client\HttpClient;

final class ApiServerClient implements ApiServerInterface
{
    private $client;

    public function __construct(HttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get Swoole HTTP Server status.
     *
     * @return array
     */
    public function status(): array
    {
        return $this->client->send('/api/server')['response']['body'];
    }

    /**
     * Shutdown Swoole HTTP Server.
     */
    public function shutdown(): void
    {
        $this->client->send('/api/server', Http::METHOD_DELETE);
    }

    /**
     * Reload Swoole HTTP Server workers.
     */
    public function reload(): void
    {
        $this->client->send('/api/server', Http::METHOD_PATCH);
    }

    /**
     * Get Swoole HTTP Server metrics.
     *
     * @return array
     */
    public function metrics(): array
    {
        return $this->client->send('/api/server/metrics')['response']['body'];
    }
}
