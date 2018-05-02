<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Driver;

use Swoole\Http\Request;
use Swoole\Http\Response;

interface DriverInterface
{
    /**
     * Handles swoole request and modifies swoole response accordingly.
     *
     * @param Request  $request
     * @param Response $response
     */
    public function handle(Request $request, Response $response): void;
}
