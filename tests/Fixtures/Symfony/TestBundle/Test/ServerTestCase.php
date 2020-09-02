<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test;

use K911\Swoole\Client\HttpClient;
use K911\Swoole\Coroutine\CoroutinePool;
use K911\Swoole\Tests\Fixtures\Symfony\TestAppKernel;
use Swoole\Coroutine\Scheduler;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ServerTestCase extends KernelTestCase
{
    public const FIXTURE_RESOURCES_DIR = __DIR__.'/../../../resources';
    public const SWOOLE_XDEBUG_CORO_WARNING_MESSAGE = 'go(): Using Xdebug in coroutines is extremely dangerous, please notice that it may lead to coredump!';
    private const COMMAND = './console';
    private const WORKING_DIRECTORY = __DIR__.'/../../app';

    protected function tearDown(): void
    {
        // Make sure everything is stopped
        $this->killAllProcessesListeningOnPort(9999);
        \sleep(self::coverageEnabled() ? 3 : 1);
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

    public static function coverageEnabled(): bool
    {
        return false !== \getenv('COVERAGE');
    }

    public function runAsCoroutineAndWait(callable $callable): void
    {
        $coroutinePool = CoroutinePool::fromCoroutines($callable);

        try {
            $coroutinePool->run();
        } catch (\RuntimeException $runtimeException) {
            if (self::SWOOLE_XDEBUG_CORO_WARNING_MESSAGE !== $runtimeException->getMessage()) {
                throw $runtimeException;
            }
        }
    }

    /**
     * Notice: This command requires running on os with "lsof" binary that supports "-i :PORT" option
     *         For example for alpine it is required to install it via: apk add lsof.
     */
    public function killAllProcessesListeningOnPort(int $port, int $timeout = 1): void
    {
        $listProcessesOnPort = new Process(['lsof', '-t', '-i', \sprintf(':%d', $port)]);
        $listProcessesOnPort->setTimeout($timeout);
        $listProcessesOnPort->run();

        if ($listProcessesOnPort->isSuccessful()) {
            foreach (\array_filter(\explode(PHP_EOL, $listProcessesOnPort->getOutput())) as $processId) {
                $kill = new Process(['kill', '-9', $processId]);
                $kill->setTimeout($timeout);
                $kill->disableOutput();
                $kill->run();
            }
        }
    }

    public function assertProcessSucceeded(Process $process): void
    {
        $status = $process->isSuccessful();
        if (!$status) {
            throw new ProcessFailedException($process);
        }

        $this->assertTrue($status);
    }

    public function assertCommandTesterDisplayContainsString(string $expected, CommandTester $commandTester): void
    {
        $this->assertStringContainsString(
            $expected,
            \preg_replace('!\s+!', ' ', \str_replace(PHP_EOL, '', $commandTester->getDisplay()))
        );
    }

    public function deferServerStop(string ...$args): void
    {
        \defer(function () use ($args): void {
            $this->serverStop(...$args);
        });
    }

    public function serverStop(string ...$args): void
    {
        $processArgs = \array_merge(['swoole:server:stop'], $args);
        $serverStop = $this->createConsoleProcess($processArgs);

        $serverStop->setTimeout(10);
        $serverStop->run();

        $this->assertProcessSucceeded($serverStop);
        $this->assertStringContainsString('Swoole server shutdown successfully', $serverStop->getOutput());
    }

    public function createConsoleProcess(array $args, array $envs = [], $input = null, ?float $timeout = 60.0): Process
    {
        $command = \array_merge([self::COMMAND], $args);

        if (!\array_key_exists('SWOOLE_TEST_XDEBUG_RESTART', $envs)) {
            if (self::coverageEnabled()) {
                $envs['COVERAGE'] = '1';
                $envs['APP_ENV'] = self::resolveEnvironment($envs['APP_ENV'] ?? null);

                if (!\array_key_exists('APP_DEBUG', $envs) && 'prod_cov' === $envs['APP_ENV']) {
                    $envs['APP_DEBUG'] = '0';
                }
            }

            if (!\array_key_exists('SWOOLE_ALLOW_XDEBUG', $envs)) {
                $envs['SWOOLE_ALLOW_XDEBUG'] = '1';
            }
        }

        return new Process($command, \realpath(self::WORKING_DIRECTORY), $envs, $input, $timeout);
    }

    public function assertHelloWorldRequestSucceeded(HttpClient $client): void
    {
        $response = $client->send('/')['response'];

        $this->assertSame(200, $response['statusCode']);
        $this->assertSame([
            'hello' => 'world!',
        ], $response['body']);
    }

    public function assertProcessFailed(Process $process): void
    {
        $this->assertFalse($process->isSuccessful());
    }

    protected static function createKernel(array $options = []): KernelInterface
    {
        $options['environment'] = self::resolveEnvironment($options['environment'] ?? null);

        return parent::createKernel($options);
    }

    protected static function getKernelClass(): string
    {
        return TestAppKernel::class;
    }

    protected function markTestSkippedIfXdebugEnabled(): void
    {
        if (\extension_loaded('xdebug')) {
            $this->markTestSkipped('Test is incompatible with Xdebug extension. Please disable it and try again. To generate code coverage use "pcov" extension.');
        }
    }

    protected function markTestSkippedIfInotifyDisabled(): void
    {
        if (!\extension_loaded('inotify')) {
            $this->markTestSkipped('Swoole Bundle HMR requires "inotify" PHP extension present and installed on the system.');
        }
    }

    protected function markTestSkippedIfSymfonyVersionIsLoverThan(string $version): void
    {
        if (\version_compare(Kernel::VERSION, $version, 'lt')) {
            $this->markTestSkipped(\sprintf('This test needs Symfony in version : %s.', $version));
        }
    }

    protected function generateUniqueHash(int $factor = 8): string
    {
        try {
            return \bin2hex(\random_bytes($factor));
        } catch (\Exception $e) {
            $array = \range(1, $factor * 2);
            \shuffle($array);

            return \implode('', $array);
        }
    }

    protected function currentUnixTimestamp(): int
    {
        return (new \DateTimeImmutable())->getTimestamp();
    }
}
