<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;

final class SwooleServerRunCommandTest extends ServerTestCase
{
    public function testRunAndCall(): void
    {
        $server = $this->createConsoleProcess([
            'swoole:server:run',
            '--host=localhost',
            '--port=9999',
        ]);

        $server->disableOutput();
        $server->setTimeout(10);
        $server->start();

        $this->goAndWait(function () use ($server): void {
            $this->deferProcessStop($server);

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $response = $client->send('/')['response'];

            $this->assertTrue(true);

            $this->assertSame(200, $response['statusCode']);
            $this->assertSame([
                'hello' => 'world!',
            ], $response['body']);
        });
    }
}
