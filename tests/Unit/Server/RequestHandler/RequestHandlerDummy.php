<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\RequestHandler;

use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class RequestHandlerDummy implements RequestHandlerInterface
{
    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, Response $response): void
    {
    }
}
