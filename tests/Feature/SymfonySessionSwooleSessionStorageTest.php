<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;
use Swoole\Coroutine;

final class SymfonySessionSwooleSessionStorageTest extends ServerTestCase
{
    protected function setUp(): void
    {
        $this->markTestSkippedIfXdebugEnabled();
    }

    public function testReturnTheSameDataForTheSameSessionId(): void
    {
        $cookieLifetime = 5;
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ], [
            'APP_ENV' => 'session',
            'COOKIE_LIFETIME' => $cookieLifetime,
        ]);

        $serverStart->setTimeout(3);
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $response1 = $client->send('/session/1')['response'];
            $this->assertSame(200, $response1['statusCode']);
            $this->assertArrayHasKey('set-cookie', $response1['headers']);
            $this->assertArrayHasKey('SWOOLESSID', $response1['cookies']);
            $sessionId1 = $response1['cookies']['SWOOLESSID'];
            $body1 = $response1['body'];

            $response2 = $client->send('/session/2')['response'];
            $this->assertArrayHasKey('SWOOLESSID', $response2['cookies']);
            $sessionId2 = $response2['cookies']['SWOOLESSID'];
            $body2 = $response2['body'];

            $this->assertSame($sessionId1, $sessionId2);
            $this->assertSame($body1, $body2);
        });
    }

    public function testDoNotReturnTheSameSessionForDifferentClients(): void
    {
        $cookieLifetime = 5;
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ], [
            'APP_ENV' => 'session',
            'COOKIE_LIFETIME' => $cookieLifetime,
        ]);

        $serverStart->setTimeout(3);
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();

            $client1 = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client1->connect());

            $response1 = $client1->send('/session/1')['response'];
            $this->assertArrayHasKey('SWOOLESSID', $response1['cookies']);
            $sessionId1 = $response1['cookies']['SWOOLESSID'];
            $body1 = $response1['body'];

            $client2 = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client2->connect());

            $response2 = $client2->send('/session/2')['response'];
            $this->assertArrayHasKey('SWOOLESSID', $response2['cookies']);
            $sessionId2 = $response2['cookies']['SWOOLESSID'];
            $body2 = $response2['body'];

            $this->assertNotSame($sessionId1, $sessionId2);
            $this->assertNotSame($body1, $body2);
        });
    }

    public function testExpireSession(): void
    {
        $cookieLifetime = 1;
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ], [
            'APP_ENV' => 'session',
            'COOKIE_LIFETIME' => $cookieLifetime,
        ]);

        $serverStart->setTimeout(3);
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function () use ($cookieLifetime): void {
            $this->deferServerStop();

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $response1 = $client->send('/session/1')['response'];
            $this->assertSame(200, $response1['statusCode']);
            $this->assertArrayHasKey('SWOOLESSID', $response1['cookies']);

            $sessionId1 = $response1['cookies']['SWOOLESSID'];
            $setCookieHeader1 = $response1['headers']['set-cookie'];
            $body1 = $response1['body'];

            Coroutine::sleep($cookieLifetime + 1);

            $response2 = $client->send('/session/2')['response'];
            $this->assertSame(200, $response2['statusCode']);
            $this->assertArrayHasKey('SWOOLESSID', $response2['cookies']);

            $sessionId2 = $response2['cookies']['SWOOLESSID'];
            $setCookieHeader2 = $response2['headers']['set-cookie'];
            $body2 = $response2['body'];

            $this->assertNotSame($sessionId1, $sessionId2);
            $this->assertNotSame($setCookieHeader1, $setCookieHeader2);
            $this->assertNotSame($body1, $body2);
        });
    }

    public function testUpdateSession(): void
    {
        $cookieLifetime = 2;
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ], [
            'APP_ENV' => 'session',
            'COOKIE_LIFETIME' => $cookieLifetime,
        ]);

        $serverStart->setTimeout(3);
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function () use ($cookieLifetime): void {
            $this->deferServerStop();

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $response1 = $client->send('/session/1')['response'];
            $this->assertSame(200, $response1['statusCode']);
            $this->assertArrayHasKey('SWOOLESSID', $response1['cookies']);

            $sessionId1 = $response1['cookies']['SWOOLESSID'];
            $setCookieHeader1 = $response1['headers']['set-cookie'];
            $body1 = $response1['body'];

            Coroutine::sleep($cookieLifetime - 1);

            $response2 = $client->send('/session/2')['response'];
            $this->assertSame(200, $response2['statusCode']);
            $this->assertArrayHasKey('SWOOLESSID', $response2['cookies']);

            $sessionId2 = $response2['cookies']['SWOOLESSID'];
            $setCookieHeader2 = $response2['headers']['set-cookie'];
            $body2 = $response2['body'];

            $this->assertSame($sessionId1, $sessionId2);
            $this->assertSame($body1, $body2);
            $this->assertNotSame($setCookieHeader1, $setCookieHeader2);
        });
    }
}
