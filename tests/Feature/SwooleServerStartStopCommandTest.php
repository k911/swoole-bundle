<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;

final class SwooleServerStartStopCommandTest extends ServerTestCase
{
    public function testStartCallStop(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ]);

        $serverStart->disableOutput();
        $serverStart->setTimeout(3);
        $serverStart->run();

        $this->assertTrue($serverStart->isSuccessful());

        $this->goAndWait(function (): void {
            $this->deferServerStop();

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $response = $client->send('/')['response'];

            $this->assertSame(200, $response['statusCode']);
            $this->assertSame([
                'hello' => 'world!',
            ], $response['body']);
        });
    }
}
