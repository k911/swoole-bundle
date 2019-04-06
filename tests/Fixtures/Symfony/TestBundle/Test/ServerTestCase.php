<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test;

use K911\Swoole\Tests\Fixtures\Symfony\TestAppKernel;
use PHPUnit\Framework\Exception;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ServerTestCase extends KernelTestCase
{
    public const SWOOLE_XDEBUG_CORO_WARNING_MESSAGE = 'go(): Using Xdebug in coroutines is extremely dangerous, please notice that it may lead to coredump!';
    private const COMMAND = './console';
    private const WORKING_DIRECTORY = __DIR__.'/../../app';

    public function createConsoleProcess(array $args, array $envs = [], $input = null, ?float $timeout = 60.0): Process
    {
        $command = \array_merge([self::COMMAND], $args);

        if (!\array_key_exists('SWOOLE_TEST_XDEBUG_RESTART', $envs)) {
            if (self::coverageEnabled()) {
                $envs['COVERAGE'] = '1';
                $envs['APP_ENV'] = self::resolveEnvironment($envs['APP_ENV'] ?? null);
            }

            if (!\array_key_exists('SWOOLE_ALLOW_XDEBUG', $envs)) {
                $envs['SWOOLE_ALLOW_XDEBUG'] = '1';
            }
        }

        return new Process($command, \realpath(self::WORKING_DIRECTORY), $envs, $input, $timeout);
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        $options['environment'] = self::resolveEnvironment($options['environment'] ?? null);

        return parent::createKernel($options);
    }

    public static function resolveEnvironment(?string $env = null): string
    {
        if (self::coverageEnabled()) {
            if ('test' === $env || null === $env) {
                $env = 'cov';
            } elseif ('_cov' !== \mb_substr($env, -4, 4)) {
                $env .= '_cov';
            }
        }

        return $env ?? 'test';
    }

    protected static function getKernelClass(): string
    {
        return TestAppKernel::class;
    }

    private function wrapAndTrap(callable $callable): callable
    {
        return function () use ($callable): void {
            try {
                $callable();
            } catch (Exception $failedException) {
                throw $failedException;
            } catch (\Throwable $exception) {
                dump($exception);
                throw $exception;
            }
        };
    }

    public function goAndWait(callable $callable): void
    {
        try {
            \go($this->wrapAndTrap($callable));
        } catch (\RuntimeException $runtimeException) {
            if (self::SWOOLE_XDEBUG_CORO_WARNING_MESSAGE !== $runtimeException->getMessage()) {
                throw $runtimeException;
            }
        }
        \swoole_event_wait();
    }

    /**
     * @param Process $process
     * @param int     $timeout seconds
     * @param int     $signal  [default=SIGKILL]
     */
    public function deferProcessStop(Process $process, int $timeout = 3, int $signal = 9): void
    {
        \defer(function () use ($process, $timeout, $signal): void {
            $process->stop($timeout, $signal);

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        });
    }

    public function deferServerStop(): void
    {
        \defer([$this, 'serverStop']);
    }

    public function serverStop(): void
    {
        $serverStop = $this->createConsoleProcess(['swoole:server:stop']);

        if (self::coverageEnabled()) {
            $serverStop->disableOutput();
        }
        $serverStop->setTimeout(3);
        $serverStop->run();

        if (!$serverStop->isSuccessful()) {
            throw new ProcessFailedException($serverStop);
        }

        if (!self::coverageEnabled()) {
            $this->assertStringContainsString('Swoole server shutdown successfully', $serverStop->getOutput());
        }
    }

    protected function tearDown(): void
    {
        // Make sure everything is stopped
        \sleep(self::coverageEnabled() ? 3 : 1);
    }

    public static function coverageEnabled(): bool
    {
        return false !== \getenv('COVERAGE');
    }
}
