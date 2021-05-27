<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Runtime\HMR;

use Assert\Assertion;
use K911\Swoole\Server\Configurator\ConfiguratorInterface;
use Swoole\Http\Server;
use Swoole\Process as SwooleProcess;
use Symfony\Component\Process\Process as SymfonyProcess;

final class FsnotifyReloaderHMR implements ConfiguratorInterface
{
    private const BIN_PATH = __DIR__.'/../../../../bin';
    private const OS_DARWIN = 'darwin';
    private const OS_LINUX = 'linux';

    private $currentOs;
    private $watchDir;
    private $verboseOutput;
    private $tickDuration;

    public function __construct(string $watchDir, string $os = null, bool $verboseOutput = false, int $tickDuration = 5)
    {
        Assertion::directory($watchDir);
        $this->watchDir = $watchDir;
        $this->currentOs = $os ?? $this->currentOperatingSystem();
        $this->verboseOutput = $verboseOutput;
        Assertion::greaterThan($tickDuration, 0);
        $this->tickDuration = $tickDuration;
    }

    public function configure(Server $server): void
    {
        $server->addProcess($this->makeReloaderProcess($this->makeFsnotifyReloaderProcess(
            $this->fsnotifyBinaryPath($this->currentOs),
            $this->watchDir,
            $server->master_pid,
            $this->verboseOutput,
        )));
    }

    private function currentOperatingSystem(): string
    {
        $os = \mb_strtolower(\php_uname('s'));

        if (false !== \mb_strpos($os, 'darwin')) {
            return self::OS_DARWIN;
        }

        if (false !== \mb_strpos($os, 'linux')) {
            return self::OS_LINUX;
        }

        throw new \RuntimeException(\sprintf('Unknown operating system "%s"', $os));
    }

    private function fsnotifyBinaryPath(string $os): string
    {
        switch ($os) {
            case self::OS_LINUX:
            case self::OS_DARWIN:
                return \sprintf('%s/fsnotify-reloader_%s_amd64', self::BIN_PATH, $os);
            default:
                throw new \RuntimeException(\sprintf('Unsupported operating sytem "%s" for fsnotify HMR', $os));
        }
    }

    private function makeReloaderProcess(SymfonyProcess $fsnotifyReloaderProcess): SwooleProcess
    {
        return new SwooleProcess(function () use ($fsnotifyReloaderProcess): void {
            \pcntl_async_signals(true);
            $signalForwarder = function (int $signalNo) use ($fsnotifyReloaderProcess): void {
                $fsnotifyReloaderProcess->signal($signalNo);
            };
            $fsnotifyReloaderProcess->start();
            foreach ([SIGTERM, SIGINT] as $signalNo) {
                \pcntl_signal($signalNo, $signalForwarder);
            }
            foreach ($fsnotifyReloaderProcess as $type => $data) {
                echo $data;
            }
        }, false, 1, false);
    }

    private function makeFsnotifyReloaderProcess(string $binPath, string $watchDir, int $masterPid, bool $verboseOutput): SymfonyProcess
    {
        $cmd = [
            $binPath,
            '-path',
            $watchDir,
            '-pid',
            $masterPid,
        ];
        if ($verboseOutput) {
            $cmd[] = '-verbose';
        }

        return new SymfonyProcess($cmd, null, null, null, null);
    }
}
