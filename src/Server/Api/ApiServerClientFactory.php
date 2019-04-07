<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Api;

use Assert\Assertion;
use K911\Swoole\Client\HttpClient;
use K911\Swoole\Server\Config\Sockets;

final class ApiServerClientFactory
{
    private $sockets;

    public function __construct(Sockets $sockets)
    {
        $this->sockets = $sockets;
    }

    public function newClient(array $options = []): ApiServerClient
    {
        Assertion::true($this->sockets->hasApiSocket(), 'Swoole HTTP Server is not configured properly. To access API trough HTTP interface, you must enable and provide proper address of configured API Server.');

        return new ApiServerClient(HttpClient::fromSocket(
            $this->sockets->getApiSocket(),
            $options
        ));
    }
}
