<?php

declare(strict_types=1);

namespace K911\Swoole\Process;

interface ProcessManagerInterface
{
    /**
     * Gracefully terminates process by id. Internally should use SIGTERM signal and allow to terminate process gracefully.
     * If timeout is exceeded it uses SIGKILL to terminate the process.
     */
    public function gracefullyTerminate(int $processId, int $timeoutSeconds = 10): void;

    /**
     * Returns true if process is running, false otherwise.
     */
    public function runningStatus(int $processId): bool;
}
