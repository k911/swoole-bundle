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
        $listCommands = $this->createConsoleProcess(['list', 'swoole']);

        $listCommands->setTimeout(self::coverageEnabled() ? 10 : 3);
        $listCommands->run();

        $this->assertProcessSucceeded($listCommands);
        $this->assertSwooleCommandsRegistered($listCommands->getOutput());
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

    public function testSwooleCommandsRegisteredWithCacheClearAppEnvProdAppDebugOff(): void
    {
        $kernel = static::createKernel(['environment' => 'prod', 'debug' => false]);
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

    public function testSwooleCommandsRegisteredWithCacheClearAppEnvExceptionHandlerCustom(): void
    {
        $kernel = static::createKernel(['environment' => 'exception_handler_custom']);
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

    public function testSwooleCommandsRegisteredWithCacheClearAppEnvSession(): void
    {
        $kernel = static::createKernel(['environment' => 'session']);
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
        $this->assertStringContainsString('swoole:server:status', $output);
    }
}
