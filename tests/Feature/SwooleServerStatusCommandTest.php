<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class SwooleServerStatusCommandTest extends ServerTestCase
{
    protected function setUp(): void
    {
        $this->markTestSkippedIfXdebugEnabled();
    }

    public function testCheckServerStatusViaProcess(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
            '--api',
            '--api-port=9998',
        ]);

        $serverStart->setTimeout(3);
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $this->runAsCoroutineAndWait(function (): void {
            $this->deferServerStop();

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $serverStatus = $this->createConsoleProcess([
                'swoole:server:status',
                '--api-host=localhost',
                '--api-port=9998',
            ]);

            $serverStatus->setTimeout(3);
            $serverStatus->run();

            $this->assertProcessSucceeded($serverStatus);

            $this->assertStringContainsString('Fetched status', $serverStatus->getOutput());
            $this->assertStringContainsString('Fetched metrics', $serverStatus->getOutput());
            $this->assertStringContainsString('Listener[0] Host', $serverStatus->getOutput());
            $this->assertStringContainsString('Requests', $serverStatus->getOutput());

            $this->assertHelloWorldRequestSucceeded($client);
        });
    }

    public function testCheckServerStatusViaCommandTester(): void
    {
        $serverStart = $this->createConsoleProcess([
            'swoole:server:start',
            '--host=localhost',
            '--port=9999',
            '--api',
            '--api-port=9998',
        ]);

        $serverStart->setTimeout(3);
        $serverStart->run();

        $this->assertProcessSucceeded($serverStart);

        $kernel = static::createKernel();
        $application = new Application($kernel);
        $command = $application->find('swoole:server:status');
        $commandTester = new CommandTester($command);

        $this->runAsCoroutineAndWait(function () use ($commandTester): void {
            $this->deferServerStop();

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $commandTester->execute([
                'command' => 'swoole:server:status',
                '--api-host' => 'localhost',
                '--api-port' => '9998',
            ]);

            $this->assertSame(0, $commandTester->getStatusCode());
            $this->assertHelloWorldRequestSucceeded($client);
        });

        $output = $commandTester->getDisplay();

        $this->assertStringContainsString('Fetched status', $output);
        $this->assertStringContainsString('Fetched metrics', $output);
        $this->assertStringContainsString('Listener[0] Host', $output);
        $this->assertStringContainsString('Requests', $output);
    }

    public function testCheckServerStatusFailWhenServerNotRunning(): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);
        $command = $application->find('swoole:server:status');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'command' => 'swoole:server:status',
            '--api-host' => 'localhost',
            '--api-port' => '9998',
        ]);

        $this->assertSame(1, $commandTester->getStatusCode());
        $this->assertCommandTesterDisplayContainsString(
            'An error occurred while connecting to the API Server. Please verify configuration.',
            $commandTester
        );
    }
}
