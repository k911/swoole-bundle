<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Bridge\Symfony\Bundle\Command\ServerStatusCommand;
use K911\Swoole\Client\HttpClient;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class SwooleServerStatusCommandTest extends ServerTestCase
{
    public function testCheckServerStatus(): void
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

        $this->assertTrue($serverStart->isSuccessful());

        $kernel = static::createKernel();
        $application = new Application($kernel);

        /** @var ServerStatusCommand $command */
        $command = $application->find('swoole:server:status');
        $command->enableTestMode();
        $commandTester = new CommandTester($command);

        $this->goAndWait(function () use ($command, $commandTester): void {
            $this->deferServerStop();

            $client = HttpClient::fromDomain('localhost', 9999, false);
            $this->assertTrue($client->connect());

            $commandTester->execute([
                'command' => $command->getName(),
                '--api-host' => 'localhost',
                '--api-port' => '9998',
            ]);

            $this->assertStringContainsString('Fetched status and metrics', $commandTester->getDisplay());
            $this->assertStringContainsString('Listener[0] Host', $commandTester->getDisplay());
            $this->assertStringContainsString('Requests', $commandTester->getDisplay());
        });
    }
}
