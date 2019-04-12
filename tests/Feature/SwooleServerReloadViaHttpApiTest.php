<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\Http;
use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;
use Swoole\Coroutine;

final class SwooleServerReloadViaHttpApiTest extends ServerTestCase
{
    private const CONTROLLER_TEMPLATE_ORIGINAL_TEXT = 'Wrong response!';
    private const CONTROLLER_TEMPLATE_REPLACE_TEXT = '%REPLACE%';
    private const CONTROLLER_TEMPLATE_SRC = __DIR__.'/../Fixtures/Symfony/TestBundle/Controller/ReplacedContentTestController.php.tmpl';
    private const CONTROLLER_TEMPLATE_DEST = __DIR__.'/../Fixtures/Symfony/TestBundle/Controller/ReplacedContentTestController.php';

    public function testStartRequestApiToReloadCallStop(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
            '--api',
            '--api-port=9998',
        ]);

        if (self::coverageEnabled()) {
            $serverStart->disableOutput();
        }
        $serverStart->setTimeout(3);
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $this->goAndWait(function (): void {
            $this->deferServerStop();
            $this->deferRestoreOriginalTemplateControllerResponse();

            $serverClient = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($serverClient->connect());

            $response1 = $serverClient->send('/test/replaced/content')['response'];

            $this->assertSame(200, $response1['statusCode']);
            $this->assertSame('Wrong response!', $response1['body']);

            $expectedResponse = 'Hello world from reloaded server worker via HTTP API!';
            $this->replaceContentInTestController($expectedResponse);
            $this->assertTestControllerResponseEquals($expectedResponse);

            $response2 = $serverClient->send('/test/replaced/content')['response'];

            $this->assertSame(200, $response2['statusCode']);
            $this->assertNotSame($expectedResponse, $response2['body']);

            $apiClient = HttpClient::fromDomain('localhost', 9998, false);
            $this->assertTrue($apiClient->connect());

            $apiClientResponse = $apiClient->send('/api/server', Http::METHOD_PATCH)['response'];
            $this->assertSame(204, $apiClientResponse['statusCode']);
            Coroutine::sleep(self::coverageEnabled() ? 3 : 1);

            $response3 = $serverClient->send('/test/replaced/content')['response'];

            $this->assertSame(200, $response3['statusCode']);
            $this->assertSame($expectedResponse, $response3['body']);
        });
    }

    private function replaceContentInTestController(string $text): void
    {
        \file_put_contents(
            self::CONTROLLER_TEMPLATE_DEST,
            \str_replace(self::CONTROLLER_TEMPLATE_REPLACE_TEXT, $text, \file_get_contents(self::CONTROLLER_TEMPLATE_SRC))
        );
    }

    private function assertTestControllerResponseEquals(string $expected): void
    {
        $this->assertSame(
            \str_replace(self::CONTROLLER_TEMPLATE_REPLACE_TEXT, $expected, \file_get_contents(self::CONTROLLER_TEMPLATE_SRC)),
            \file_get_contents(self::CONTROLLER_TEMPLATE_DEST)
        );
    }

    private function deferRestoreOriginalTemplateControllerResponse(): void
    {
        \defer(function (): void {
            $this->replaceContentInTestController(self::CONTROLLER_TEMPLATE_ORIGINAL_TEXT);
        });
    }
}
