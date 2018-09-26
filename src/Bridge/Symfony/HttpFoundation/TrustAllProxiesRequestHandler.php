<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpFoundation;

use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use K911\Swoole\Server\Runtime\BootableInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

final class TrustAllProxiesRequestHandler implements RequestHandlerInterface, BootableInterface
{
    private $decorated;
    private $trustAllProxies;

    public function __construct(RequestHandlerInterface $decorated, bool $trustAllProxies = false)
    {
        $this->decorated = $decorated;
        $this->trustAllProxies = $trustAllProxies;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        if (isset($runtimeConfiguration['trustAllProxies']) && true === $runtimeConfiguration['trustAllProxies']) {
            $this->trustAllProxies = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handle(SwooleRequest $request, SwooleResponse $response): void
    {
        if ($this->trustAllProxies) {
            SymfonyRequest::setTrustedProxies(['127.0.0.1', $request->server['remote_addr']], SymfonyRequest::HEADER_X_FORWARDED_ALL);
        }

        $this->decorated->handle($request, $response);
    }
}
