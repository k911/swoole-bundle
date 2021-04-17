<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;

final class SwooleServerCustomPidFileTest extends ServerTestCase
{
    public function testStartServerOnCustomPidFileLocation(): void
    {
        $this->markTestSkippedIfXdebugEnabled();

        $pidFile = $this->generateNotExistingCustomPidFile();

        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
            \sprintf('--pid-file=%s', $pidFile),
        ]);

        self::assertFileDoesNotExist($pidFile);

        $this->assertStartServerSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function () use ($pidFile): void {
            $this->deferServerStop(\sprintf('--pid-file=%s', $pidFile));

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $this->assertFileExists($pidFile);
            $this->assertIsNumeric(\file_get_contents($pidFile));

            $this->assertHelloWorldRequestSucceeded($client);
        });
    }

    public function testTryToStartServerOnReadOnlyExistingPidFile(): void
    {
        $pidFile = $this->setUpExistingReadOnlyPidFile();

        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
            \sprintf('--pid-file=%s', $pidFile),
        ]);

        self::assertFileExists($pidFile);
        self::assertFileIsNotWritable($pidFile);

        $serverStart->setTimeout(3);
        $serverStart->run();

        $this->assertProcessFailed($serverStart);
        self::assertStringContainsString('Could not create pid file', $serverStart->getErrorOutput());
    }

    private function generateNotExistingCustomPidFile(): string
    {
        $hash = \bin2hex(\random_bytes(8));

        return \sprintf('%s/custom-pid-file-%s.pid', self::FIXTURE_RESOURCES_DIR, $hash);
    }

    private function setUpExistingReadOnlyPidFile(): string
    {
        $hash = \bin2hex(\random_bytes(8));
        $readOnlyFile = \sprintf('%s/existing-readonly-pid-file-%s.pid', self::FIXTURE_RESOURCES_DIR, $hash);

        self::assertNotFalse(\file_put_contents($readOnlyFile, '-9999'));
        self::assertTrue(\chmod($readOnlyFile, 0400));

        return $readOnlyFile;
    }
}
