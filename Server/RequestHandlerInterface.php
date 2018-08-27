<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Server;

use Swoole\Http\Request;
use Swoole\Http\Response;

interface RequestHandlerInterface
{
    /**
     * Override driver configuration at runtime.
     *
     * @param array $runtimeConfiguration
     */
    public function boot(array $runtimeConfiguration = []): void;

    /**
     * Handles swoole request and modifies swoole response accordingly.
     *
     * @param \Swoole\Http\Request  $request
     * @param \Swoole\Http\Response $response
     */
    public function handle(Request $request, Response $response): void;
}
