<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpKernel;

use K911\Swoole\Bridge\Symfony\Container\CoWrapper;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use K911\Swoole\Server\Runtime\BootableInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Runtime;

final class CoroutineHttpKernelRequestHandler implements RequestHandlerInterface, BootableInterface
{
    private $requestHandler;
    private $coWrapper;
    private $wereCoroutinesEnabled = false;

    public function __construct(RequestHandlerInterface $requestHandler, CoWrapper $coWrapper)
    {
        $this->requestHandler = $requestHandler;
        $this->coWrapper = $coWrapper;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        $this->requestHandler->boot();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function handle(SwooleRequest $request, SwooleResponse $response): void
    {
        $this->enableCoroutines();
        $this->coWrapper->defer();
        $this->requestHandler->handle($request, $response);
    }

    private function enableCoroutines(): void
    {
        if ($this->wereCoroutinesEnabled) {
            return;
        }

        $this->wereCoroutinesEnabled = true;
        Runtime::enableCoroutine();
    }
}
