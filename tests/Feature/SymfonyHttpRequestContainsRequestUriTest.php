<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;

class SymfonyHttpRequestContainsRequestUriTest extends ServerTestCase
{
    protected function setUp(): void
    {
        $this->markTestSkippedIfXdebugEnabled();
    }

    /*
     * Test whether current Symfony's Request->getRequestUri() is working
     * @see https://github.com/k911/swoole-bundle/issues/268
     */
    public function testWhetherCurrentSymfonyHttpRequestContainsRequestUri(): void
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

            $uri = '/http/request/uri?test1=1&test2=test3';
            $response = $client->send('/http/request/uri?test1=1&test2=test3')['response'];

            $this->assertSame(200, $response['statusCode']);
            $this->assertSame([
                'requestUri' => $uri,
            ], $response['body']);
        });

        $serverRun->stop();
    }
}
