<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;
use Swoole\Coroutine;

final class SwooleServerReloadCommandTest extends ServerTestCase
{
    private const CONTROLLER_TEMPLATE_ORIGINAL_TEXT = 'Wrong response!';
    private const CONTROLLER_TEMPLATE_REPLACE_TEXT = '%REPLACE%';
    private const CONTROLLER_TEMPLATE_SRC = __DIR__.'/../Fixtures/Symfony/TestBundle/Controller/ReplacedContentTestController.php.tmpl';
    private const CONTROLLER_TEMPLATE_DEST = __DIR__.'/../Fixtures/Symfony/TestBundle/Controller/ReplacedContentTestController.php';

    public function testStartCallReloadCallStop(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
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

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $response1 = $client->send('/test/replaced/content')['response'];

            $this->assertSame(200, $response1['statusCode']);
            $this->assertSame('Wrong response!', $response1['body']);

            $expectedResponse = 'Hello world from reloaded server worker!';
            $this->replaceContentInTestController($expectedResponse);
            $this->assertTestControllerResponseEquals($expectedResponse);

            $response2 = $client->send('/test/replaced/content')['response'];

            $this->assertSame(200, $response2['statusCode']);
            $this->assertNotSame($expectedResponse, $response2['body']);

            $this->runSwooleServerReload();
            Coroutine::sleep(self::coverageEnabled() ? 3 : 1);

            $response3 = $client->send('/test/replaced/content')['response'];

            $this->assertSame(200, $response3['statusCode']);
            $this->assertSame($expectedResponse, $response3['body']);
        });
    }

    private function runSwooleServerReload(): void
    {
        $serverReload = $this->createConsoleProcess(['swoole:server:reload']);

        if (self::coverageEnabled()) {
            $serverReload->disableOutput();
        }
        $serverReload->setTimeout(3);
        $serverReload->run();

        $this->assertProcessSucceeded($serverReload);

        if (!self::coverageEnabled()) {
            $this->assertStringContainsString('Swoole HTTP Server\'s workers reloaded successfully', $serverReload->getOutput());
        }
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
