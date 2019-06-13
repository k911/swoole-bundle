<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Config;

final class ListenerConfig
{
    public const CONFIG_WEBSOCKET_PROTOCOL = 'open_websocket_protocol';
    public const CONFIG_HTTP_PROTOCOL = 'open_http_protocol';
    public const CONFIG_HTTP2_PROTOCOL = 'open_http2_protocol';

    private $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function all(): array
    {
        return $this->config;
    }

    public function enableWebsocketProtocol(): void
    {
        $this->config[self::CONFIG_WEBSOCKET_PROTOCOL] = true;
    }

    public function disableWebsocketProtocol(): void
    {
        $this->config[self::CONFIG_WEBSOCKET_PROTOCOL] = false;
    }

    public function enableHttpProtocol(): void
    {
        $this->config[self::CONFIG_HTTP_PROTOCOL] = true;
    }

    public function disableHttpProtocol(): void
    {
        $this->config[self::CONFIG_HTTP_PROTOCOL] = false;
    }

    public function enableHttp2Protocol(): void
    {
        $this->config[self::CONFIG_HTTP2_PROTOCOL] = true;
    }

    public function disableHttp2Protocol(): void
    {
        $this->config[self::CONFIG_HTTP2_PROTOCOL] = false;
    }
}
