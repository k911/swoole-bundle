<?php

declare(strict_types=1);

namespace K911\Swoole\Server\RequestHandler\ExceptionHandler;

use K911\Swoole\Client\Http;
use K911\Swoole\Component\ExceptionArrayTransformer;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Throwable;

final class JsonExceptionHandler implements ExceptionHandlerInterface
{
    private $exceptionArrayTransformer;
    private $verbosity;

    public function __construct(ExceptionArrayTransformer $exceptionArrayTransformer, string $verbosity = 'default')
    {
        $this->exceptionArrayTransformer = $exceptionArrayTransformer;
        $this->verbosity = $verbosity;
    }

    public function handle(Request $request, Throwable $exception, Response $response): void
    {
        $data = $this->exceptionArrayTransformer->transform($exception, $this->verbosity);

        $response->header(Http::HEADER_CONTENT_TYPE, Http::CONTENT_TYPE_APPLICATION_JSON);
        $response->status(500);
        $response->end(\json_encode($data, \JSON_THROW_ON_ERROR));
    }
}
