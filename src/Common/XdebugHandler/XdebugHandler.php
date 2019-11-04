<?php

declare(strict_types=1);

namespace K911\Swoole\Common\XdebugHandler;

use Generator;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Custom Xdebug handler which resolves issues with colors supports, and signal forwarding to child process.
 *
 * @see https://github.com/composer/xdebug-handler/blob/master/src/XdebugHandler.php
 */
final class XdebugHandler
{
    private const SIGNALS_MAP = [
        2 => 'SIGINT',
        15 => 'SIGTERM',
        10 => 'SIGUSR1',
        12 => 'SIGUSR2',
    ];

    private $allowXdebugEnvName;

    public function __construct(string $allowXdebugEnvName = 'SWOOLE_ALLOW_XDEBUG')
    {
        $this->allowXdebugEnvName = $allowXdebugEnvName;
    }

    public function shouldRestart(): bool
    {
        return !$this->isAllowed() && \extension_loaded('xdebug');
    }

    public function canBeRestarted(): bool
    {
        return \extension_loaded('pcntl');
    }

    public function allowXdebugEnvName(): string
    {
        return $this->allowXdebugEnvName;
    }

    public function prepareRestartedProcess(): Process
    {
        $command = [PHP_BINARY, '-n', '-c', $this->createPreparedTempIniFile()];
        $currentCommand = $_SERVER['argv'];
        $command = \array_merge($command, $currentCommand);

        $process = new Process($command, null, $this->prepareEnvs());
        $process->setTty(Process::isTtySupported());
        $process->setTimeout(null);

        return $process;
    }

    public function forwardSignals(Process $process): void
    {
        \pcntl_async_signals(true);

        $signalForwarder = function (int $signalNo) use ($process): void {
            $process->signal($signalNo);
        };

        foreach (\array_keys(self::SIGNALS_MAP) as $signalNo) {
            \pcntl_signal($signalNo, $signalForwarder);
        }
    }

    private function isAllowed(): bool
    {
        return false !== \getenv($this->allowXdebugEnvName);
    }

    private function prepareEnvs(): array
    {
        $envs = [];
        $lines = \getenv('LINES');
        $columns = \getenv('COLUMNS');
        if (false !== $lines) {
            $envs['LINES'] = $lines;
        }
        if (false !== $columns) {
            $envs['COLUMNS'] = $columns;
        }

        return $envs;
    }

    private function createPreparedTempIniFile(): string
    {
        $tempIniFilePath = \tempnam(\sys_get_temp_dir(), '');
        if (false === $tempIniFilePath) {
            throw new RuntimeException('Could not generate temporary file');
        }

        $preparedContent = $this->parsePhpIniContent($this->generateLoadedPhpIniFiles());

        if (false === @\file_put_contents($tempIniFilePath, $preparedContent)) {
            throw new RuntimeException(\sprintf('Could not write prepared temporary php ini file to "%s".', $tempIniFilePath));
        }

        return $tempIniFilePath;
    }

    private function generateLoadedPhpIniFiles(): Generator
    {
        $loadedIniFile = \php_ini_loaded_file();
        if (!empty($loadedIniFile)) {
            yield $loadedIniFile;
        }

        foreach (\explode(',', \php_ini_scanned_files()) as $scanned) {
            $preparedScanned = \trim($scanned);

            if ('' !== $preparedScanned) {
                yield $preparedScanned;
            }
        }
    }

    private function parsePhpIniContent(iterable $iniFiles): string
    {
        $content = '';
        $regex = '/^\s*(zend_extension\s*=.*xdebug.*)$/mi';

        foreach ($iniFiles as $iniFile) {
            $iniContent = \file_get_contents($iniFile);
            if (false === $iniContent) {
                throw new RuntimeException(\sprintf('Could not get contents of ini file "%s".', $iniFile));
            }

            $data = \preg_replace($regex, ';$1', $iniContent);
            $content .= $data.PHP_EOL;
        }

        // Merge loaded settings into our ini content, if it is valid
        if ($config = \parse_ini_string($content)) {
            $loaded = \ini_get_all(null, false);
            $content .= $this->mergeLoadedConfig($loaded, $config);
        }

        // Work-around for https://bugs.php.net/bug.php?id=75932
        $content .= 'opcache.enable_cli=0'.PHP_EOL;

        return $content;
    }

    /**
     * Returns default, changed and command-line ini settings.
     *
     * @param array $loadedConfig All current ini settings
     * @param array $iniConfig    Settings from user ini files
     */
    private function mergeLoadedConfig(array $loadedConfig, array $iniConfig): string
    {
        $content = '';

        foreach ($loadedConfig as $name => $value) {
            // Value will either be null, string or array (HHVM only)
            if ('apc.mmap_file_mask' === $name || !\is_string($value) || !\is_string($name) || 0 === \mb_strpos($name, 'xdebug')) {
                continue;
            }

            if (!isset($iniConfig[$name]) || $iniConfig[$name] !== $value) {
                // Double-quote escape each value
                $content .= $name.'="'.\addcslashes($value, '\\"').'"'.PHP_EOL;
            }
        }

        return $content;
    }
}
