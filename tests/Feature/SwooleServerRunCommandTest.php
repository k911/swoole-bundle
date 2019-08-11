<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;

final class SwooleServerRunCommandTest extends ServerTestCase
{
    protected function setUp(): void
    {
        $this->markTestSkippedIfXdebugEnabled();
    }

    public function testRunAndCall(): void
    {
        $serverRun = $this->createConsoleProcess([
            'swoole:server:run',
            '--host=localhost',
            '--port=9999',
        ]);

        $serverRun->setTimeout(10);
        $serverRun->start();

        $this->runAsCoroutineAndWait(function (): void {
            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $this->assertHelloWorldRequestSucceeded($client);
        });

        $serverRun->stop();
    }

    public function testRunAndCallOnReactorRunningMode(): void
    {
        $serverRun = $this->createConsoleProcess([
            'swoole:server:run',
            '--host=localhost',
            '--port=9999',
        ], ['APP_ENV' => 'reactor']);

        $serverRun->setTimeout(10);
        $serverRun->start();

        $this->runAsCoroutineAndWait(function (): void {
            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $this->assertHelloWorldRequestSucceeded($client);
        });

        $serverRun->stop();

        $this->killAllProcessesListeningOnPort(9999);
    }
}
