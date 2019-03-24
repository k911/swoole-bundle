<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Bridge\Symfony\Bundle\Command\AbstractServerStartCommand;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class SwooleServerRunXdebugRestartedCommandTest extends ServerTestCase
{
    public function testRunAndCall(): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        /** @var AbstractServerStartCommand $command */
        $command = $application->find('swoole:server:start');
        $command->enableTestMode();

        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'command' => $command->getName(),
            '--host' => 'localhost',
            '--port' => '9999',
        ]);

        $this->assertSame(0, $commandTester->getStatusCode());
        if (\extension_loaded('xdebug')) {
            $this->assertStringContainsString('Restarting command without Xdebug..', $commandTester->getDisplay());
        }
    }
}
