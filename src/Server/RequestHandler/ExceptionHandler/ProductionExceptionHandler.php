<?php

declare(strict_types=1);

namespace K911\Swoole\Server\RequestHandler\ExceptionHandler;

use K911\Swoole\Client\Http;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Throwable;

final class ProductionExceptionHandler implements ExceptionHandlerInterface
{
    public const ERROR_MESSAGE = 'An unexpected fatal error has occurred. Please report this incident to the administrator of this service.';

    public function handle(Request $request, Throwable $exception, Response $response): void
    {
        $response->header(Http::HEADER_CONTENT_TYPE, Http::CONTENT_TYPE_TEXT_PLAIN);
        $response->status(500);
        $response->end(self::ERROR_MESSAGE);
    }
}
