<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Server\RequestHandler;

use Swoole\Http\Request;
use Swoole\Http\Response;

interface RequestHandlerInterface
{
    /**
     * Handles swoole request and modifies swoole response accordingly.
     *
     * @param \Swoole\Http\Request  $request
     * @param \Swoole\Http\Response $response
     */
    public function handle(Request $request, Response $response): void;
}
