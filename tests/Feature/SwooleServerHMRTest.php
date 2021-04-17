<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;
use Swoole\Coroutine;

final class SwooleServerHMRTest extends ServerTestCase
{
    private const CONTROLLER_TEMPLATE_ORIGINAL_TEXT = 'Wrong response!';
    private const CONTROLLER_TEMPLATE_REPLACE_TEXT = '%REPLACE%';
    private const CONTROLLER_TEMPLATE_SRC = __DIR__.'/../Fixtures/Symfony/TestBundle/Controller/ReplacedContentTestController.php.tmpl';
    private const CONTROLLER_TEMPLATE_DEST = __DIR__.'/../Fixtures/Symfony/TestBundle/Controller/ReplacedContentTestController.php';

    protected function setUp(): void
    {
        $this->markTestSkippedIfXdebugEnabled();
        $this->markTestSkippedIfInotifyDisabled();
    }

    public function testStartCallHMRCallStopWithAutoRegistration(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ], ['APP_ENV' => 'auto']);

        $this->assertStartServerSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();
            $this->deferRestoreOriginalTemplateControllerResponse();

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $response1 = $client->send('/test/replaced/content')['response'];

            $this->assertSame(200, $response1['statusCode']);
            $this->assertSame('Wrong response!', $response1['body']);

            Coroutine::sleep(self::coverageEnabled() ? 5 : 3);

            $expectedResponse = 'Hello world from swoole reloaded worker by HMR!';
            $this->replaceResponseInTestController($expectedResponse);
            $this->assertTestControllerResponseEquals($expectedResponse);

            Coroutine::sleep(self::coverageEnabled() ? 5 : 3);

            $response3 = $client->send('/test/replaced/content')['response'];

            $this->assertSame(200, $response3['statusCode']);
            $this->assertSame($expectedResponse, $response3['body']);
        });
    }

    public function testHMRDisabledByDefaultOnProduction(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
        ], ['APP_ENV' => 'prod']);

        $this->assertStartServerSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();
            $this->deferRestoreOriginalTemplateControllerResponse();

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $response1 = $client->send('/test/replaced/content')['response'];

            $this->assertSame(200, $response1['statusCode']);

            $expectedResponse = 'Wrong response!';
            $this->assertSame($expectedResponse, $response1['body']);

            Coroutine::sleep(self::coverageEnabled() ? 5 : 3);

            $this->replaceResponseInTestController($expectedResponse);
            $this->assertTestControllerResponseEquals($expectedResponse);

            Coroutine::sleep(self::coverageEnabled() ? 5 : 3);

            $response3 = $client->send('/test/replaced/content')['response'];

            $this->assertSame(200, $response3['statusCode']);
            $this->assertSame($expectedResponse, $response3['body']);
        });
    }

    private function replaceResponseInTestController(string $text): void
    {
        \file_put_contents(
            self::CONTROLLER_TEMPLATE_DEST,
            \str_replace(self::CONTROLLER_TEMPLATE_REPLACE_TEXT, $text, \file_get_contents(self::CONTROLLER_TEMPLATE_SRC))
        );
        \touch(self::CONTROLLER_TEMPLATE_DEST);
    }

    private function assertTestControllerResponseEquals(string $expected): void
    {
        self::assertSame(
            \str_replace(self::CONTROLLER_TEMPLATE_REPLACE_TEXT, $expected, \file_get_contents(self::CONTROLLER_TEMPLATE_SRC)),
            \file_get_contents(self::CONTROLLER_TEMPLATE_DEST)
        );
    }

    private function deferRestoreOriginalTemplateControllerResponse(): void
    {
        \defer(function (): void {
            $this->replaceResponseInTestController(self::CONTROLLER_TEMPLATE_ORIGINAL_TEXT);
        });
    }
}
