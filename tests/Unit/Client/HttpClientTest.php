<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Client;

use K911\Swoole\Client\HttpClient;
use PHPUnit\Framework\TestCase;

final class HttpClientTest extends TestCase
{
    public function testThatClientSerializesProperly(): void
    {
        $host = 'fake';
        $port = 8888;
        $ssl = false;
        $options = ['testing' => 1];

        $client = HttpClient::fromDomain($host, $port, $ssl, $options);

        $expected = [
            'host' => $host,
            'port' => $port,
            'ssl' => $ssl,
            'options' => $options,
        ];

        self::assertSame(\json_encode($expected, JSON_THROW_ON_ERROR), $client->serialize());

        $serializedClient = \serialize($client);
        $unserializedClient = \unserialize($serializedClient, ['allowed_classes' => [HttpClient::class]]);

        self::assertInstanceOf(HttpClient::class, $unserializedClient);
    }
}
