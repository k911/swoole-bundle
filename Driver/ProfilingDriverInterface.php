<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Driver;

interface ProfilingDriverInterface extends DriverInterface
{
    /**
     * Determines whether profiling has been enabled.
     *
     * @return bool
     */
    public function profilingEnabled(): bool;

    /**
     * Profiles current driver state.
     *
     * E.g. Prints analyzed stats to output
     *
     * @param string $when
     */
    public function profile(string $when = null): void;
}
