<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;

final class StaticFilesServingTest extends ServerTestCase
{
    public function testAdvancedStaticFilesServerWithAutoRegistration(): void
    {
        $serverRun = $this->createConsoleProcess([
            'swoole:server:run',
            '--host=localhost',
            '--port=9999',
        ], ['APP_ENV' => 'auto']);

        $serverRun->setTimeout(10);
        $serverRun->start();

        $this->runAsCoroutineAndWait(function (): void {
            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $response = $client->send('/robots.txt')['response'];

            $this->assertSame(200, $response['statusCode']);
            $this->assertSame('text/plain', $response['headers']['content-type']);
            $expectedResponseBody = <<< 'EOF'
                User-agent: *
                Disallow: /

                EOF;
            $this->assertSame($expectedResponseBody, $response['body']);
        });

        $serverRun->stop();
    }

    public function testDefaultSwooleStaticFilesServing(): void
    {
        $serverRun = $this->createConsoleProcess([
            'swoole:server:run',
            '--host=localhost',
            '--port=9999',
        ], ['APP_ENV' => 'static']);

        $serverRun->setTimeout(10);
        $serverRun->start();

        $this->runAsCoroutineAndWait(function (): void {
            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $response = $client->send('/robots.txt')['response'];

            $this->assertSame(200, $response['statusCode']);
            $this->assertSame('text/plain', $response['headers']['content-type']);
            $expectedResponseBody = <<< 'EOF'
                User-agent: *
                Disallow: /

                EOF;
            $this->assertSame($expectedResponseBody, $response['body']);
        });

        $serverRun->stop();
    }

    public function testDisabledStaticFilesServingOnProductionByDefault(): void
    {
        $serverRun = $this->createConsoleProcess([
            'swoole:server:run',
            '--host=localhost',
            '--port=9999',
        ], ['APP_ENV' => 'prod']);

        $serverRun->setTimeout(10);
        $serverRun->start();

        $this->runAsCoroutineAndWait(function (): void {
            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $response = $client->send('/robots.txt')['response'];

            $this->assertSame(404, $response['statusCode']);
        });

        $serverRun->stop();
    }
}
