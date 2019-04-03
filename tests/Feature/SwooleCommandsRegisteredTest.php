<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class SwooleCommandsRegisteredTest extends ServerTestCase
{
    public function testSwooleCommandsRegisteredCallViaProcess(): void
    {
        $process = $this->createConsoleProcess(['list', 'swoole']);

        $process->setTimeout(3);
        $process->run();

        $this->assertTrue($process->isSuccessful());
        $this->assertSwooleCommandsRegistered($process->getOutput());
    }

    public function testSwooleCommandsRegisteredWithCacheClear(): void
    {
        $kernel = static::createKernel();
        $application = new Application($kernel);

        $cacheClear = $application->find('cache:clear');
        $commandTester = new CommandTester($cacheClear);
        $commandTester->execute([]);
        $this->assertSame(0, $commandTester->getStatusCode());

        $listCommand = $application->find('list');
        $commandTester = new CommandTester($listCommand);
        $commandTester->execute(['swoole']);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertSwooleCommandsRegistered($commandTester->getDisplay());
    }

    public function assertSwooleCommandsRegistered(string $output): void
    {
        $this->assertStringContainsString('swoole:server:profile', $output);
        $this->assertStringContainsString('swoole:server:reload', $output);
        $this->assertStringContainsString('swoole:server:run', $output);
        $this->assertStringContainsString('swoole:server:start', $output);
        $this->assertStringContainsString('swoole:server:stop', $output);
    }
}
