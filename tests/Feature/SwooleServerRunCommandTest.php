<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;

final class SwooleServerRunCommandTest extends ServerTestCase
{
    public function testRunAndCall(): void
    {
        $serverRun = $this->createConsoleProcess([
            'swoole:server:run',
            '--host=localhost',
            '--port=9999',
        ]);

        if (self::coverageEnabled()) {
            $serverRun->disableOutput();
        }
        $serverRun->setTimeout(10);
        $serverRun->start();

        $this->goAndWait(function () use ($serverRun): void {
            $this->deferProcessStop($serverRun);

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
