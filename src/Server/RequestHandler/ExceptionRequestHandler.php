<?php

declare(strict_types=1);

namespace K911\Swoole\Server\RequestHandler;

use K911\Swoole\Server\RequestHandler\ExceptionHandler\ExceptionHandlerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Throwable;

final class ExceptionRequestHandler implements RequestHandlerInterface
{
    private $decorated;
    private $exceptionHandler;

    public function __construct(RequestHandlerInterface $decorated, ExceptionHandlerInterface $exceptionHandler)
    {
        $this->decorated = $decorated;
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, Response $response): void
    {
        try {
            $this->decorated->handle($request, $response);
        } catch (Throwable $exception) {
            $this->exceptionHandler->handle($request, $exception, $response);
        }
    }
}
