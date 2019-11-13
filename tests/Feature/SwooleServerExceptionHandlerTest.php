<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;

final class SwooleServerExceptionHandlerTest extends ServerTestCase
{
    protected function setUp(): void
    {
        $this->markTestSkippedIfXdebugEnabled();
    }

    public function testCatchException(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ]);

        $serverStart->setTimeout(3);
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();

            $client1 = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client1->connect());
            $response1 = $client1->send('/throwable/exception')['response'];
            $this->assertSame(500, $response1['statusCode']);
            $this->assertStringContainsString('text/html', $response1['headers']['content-type']);
            $this->assertStringContainsString('RuntimeException', $response1['body']);
            $this->assertStringContainsString('An exception has occurred', $response1['body']);

            $client2 = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client2->connect());
            $response2 = $client2->send('/throwable/error')['response'];
            $this->assertSame(500, $response2['statusCode']);
            $this->assertStringContainsString('application/json', $response2['headers']['content-type']);
            $this->assertEquals('Error', $response2['body']['class']);
            $this->assertEquals('Critical failure', $response2['body']['message']);
            $this->assertEquals(5000, $response2['body']['code']);
            $this->assertArrayHasKey('file', $response2['body']);
            $this->assertArrayHasKey('line', $response2['body']);
            $this->assertArrayHasKey('trace', $response2['body']);
            $this->assertArrayHasKey('previous', $response2['body']);
        });
    }

    public function testCatchExceptionOnReactorRunningMode(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ], ['APP_ENV' => 'reactor']);

        $serverStart->setTimeout(3);
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();

            $client1 = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client1->connect());
            $response1 = $client1->send('/throwable/exception')['response'];
            $this->assertSame(500, $response1['statusCode']);
            $this->assertStringContainsString('text/html', $response1['headers']['content-type']);
            $this->assertStringContainsString('RuntimeException', $response1['body']);
            $this->assertStringContainsString('An exception has occurred', $response1['body']);

            $client2 = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client2->connect());
            $response2 = $client2->send('/throwable/error')['response'];
            $this->assertSame(500, $response2['statusCode']);
            $this->assertStringContainsString('application/json', $response2['headers']['content-type']);
            $this->assertEquals('Error', $response2['body']['class']);
            $this->assertEquals('Critical failure', $response2['body']['message']);
            $this->assertEquals(5000, $response2['body']['code']);
            $this->assertArrayHasKey('file', $response2['body']);
            $this->assertArrayHasKey('line', $response2['body']);
            $this->assertArrayHasKey('trace', $response2['body']);
            $this->assertArrayHasKey('previous', $response2['body']);
        });
    }

    public function testCatchExceptionViaProductionExceptionHandler(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ], ['APP_ENV' => 'prod', 'APP_DEBUG' => '0']);

        $serverStart->setTimeout(3);
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();

            $client1 = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client1->connect());
            $response1 = $client1->send('/throwable/exception')['response'];
            $this->assertSame(500, $response1['statusCode']);
            $this->assertStringContainsString('text/html', $response1['headers']['content-type']);
            $this->assertStringContainsString('An Error Occurred: Internal Server Error', $response1['body']);

            $client2 = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client2->connect());
            $response2 = $client2->send('/throwable/error')['response'];
            $this->assertSame(500, $response2['statusCode']);
            $this->assertStringContainsString('text/plain', $response2['headers']['content-type']);
            $this->assertStringContainsString('An unexpected fatal error has occurred. Please report this incident to the administrator of this service.', $response2['body']);
        });
    }

    public function testCatchExceptionWithNoTrace(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ], ['APP_DEBUG' => '0']);

        $serverStart->setTimeout(3);
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());
            $response = $client->send('/throwable/error')['response'];
            $this->assertSame(500, $response['statusCode']);
            $this->assertStringContainsString('application/json', $response['headers']['content-type']);
            $this->assertEquals('Error', $response['body']['class']);
            $this->assertEquals('Critical failure', $response['body']['message']);
            $this->assertEquals(5000, $response['body']['code']);
            $this->assertArrayHasKey('file', $response['body']);
            $this->assertArrayHasKey('line', $response['body']);
            $this->assertArrayNotHasKey('trace', $response['body']);
            $this->assertArrayHasKey('previous', $response['body']);
            $this->assertArrayNotHasKey('trace', $response['body']['previous']);
        });
    }

    public function testExceptionHandlerJsonDefaultVerbosity(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ], ['APP_ENV' => 'exception_handler_json']);

        $serverStart->setTimeout(3);
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());
            $response = $client->send('/throwable/error')['response'];
            $this->assertSame(500, $response['statusCode']);
            $this->assertStringContainsString('application/json', $response['headers']['content-type']);
            $this->assertEquals('Critical failure', $response['body']['message']);
            $this->assertEquals(5000, $response['body']['code']);
            $this->assertArrayNotHasKey('class', $response['body']);
            $this->assertArrayNotHasKey('file', $response['body']);
            $this->assertArrayNotHasKey('line', $response['body']);
            $this->assertArrayNotHasKey('trace', $response['body']);
            $this->assertArrayHasKey('previous', $response['body']);
            $this->assertArrayNotHasKey('class', $response['body']['previous']);
            $this->assertArrayNotHasKey('file', $response['body']['previous']);
            $this->assertArrayNotHasKey('line', $response['body']['previous']);
            $this->assertArrayNotHasKey('trace', $response['body']['previous']);
        });
    }

    public function testCustomExceptionHandler(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ], ['APP_ENV' => 'exception_handler_custom']);

        $serverStart->setTimeout(3);
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());
            $response = $client->send('/throwable/error')['response'];
            $this->assertSame(500, $response['statusCode']);
            $this->assertStringContainsString('text/plain', $response['headers']['content-type']);
            $this->assertStringContainsString('Very custom exception handler', $response['body']);
        });
    }
}
