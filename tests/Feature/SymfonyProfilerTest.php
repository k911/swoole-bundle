<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;
use Swoole\Coroutine;

final class SymfonyProfilerTest extends ServerTestCase
{
    protected function setUp(): void
    {
        // problem with messenger support in symfony profiler in symfony 4.3
        $this->markTestSkippedIfSymfonyVersionIsLoverThan('4.4.0');
        $this->markTestSkippedIfXdebugEnabled();
    }

    public function testSymfonyProfilerTwigDebugLink(): void
    {
        $serverRun = $this->createConsoleProcess([
            'swoole:server:run',
            '--host=localhost',
            '--port=9999',
        ], ['APP_ENV' => 'profiler']);

        $serverRun->setTimeout(self::coverageEnabled() ? 10 : 5);
        $serverRun->start();

        $this->runAsCoroutineAndWait(function (): void {
            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());
            $this->assertHelloWorldRequestSucceeded($client);

            $response = $client->send('/twig')['response'];

            $this->assertSame(200, $response['statusCode']);
            $this->assertNotEmpty($response['headers']['x-debug-token']);
            $debugToken = $response['headers']['x-debug-token'];

            Coroutine::sleep(2);

            $client2 = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client2->connect());

            $profilerResponse = $client2->send('/_profiler/'.$debugToken)['response'];
            $this->assertSame(200, $profilerResponse['statusCode']);
            $this->assertStringContainsString('Profiler', $profilerResponse['body']);
        });

        $serverRun->stop();
    }
}
