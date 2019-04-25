<?php

declare(strict_types=1);

namespace K911\Swoole\Server\RequestHandler;

use Swoole\Http\Request;
use Swoole\Http\Response;

interface RequestHandlerInterface
{
    /**
     * Handles swoole request and modifies swoole response accordingly.
     *
     * @param Request  $request
     * @param Response $response
     */
    public function handle(Request $request, Response $response): void;
}
