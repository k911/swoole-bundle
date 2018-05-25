<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Driver;

interface DriverInterface extends RequestHandlerInterface
{
    /**
     * Override configuration at runtime.
     *
     * @param array $configuration
     */
    public function boot(array $configuration = []): void;
}
