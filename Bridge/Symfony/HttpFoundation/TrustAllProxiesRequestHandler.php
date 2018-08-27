<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation;

use App\Bundle\SwooleBundle\Server\RequestHandlerInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

final class TrustAllProxiesRequestHandler implements RequestHandlerInterface
{
    private $decorated;
    private $trustAllProxies;

    public function __construct(RequestHandlerInterface $decorated)
    {
        $this->decorated = $decorated;
        $this->trustAllProxies = false;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        if (isset($runtimeConfiguration['trustedProxies']) && \in_array('*', $runtimeConfiguration['trustedProxies'], true)) {
            $this->trustAllProxies = true;
            $runtimeConfiguration['trustedProxies'] = \array_filter($runtimeConfiguration['trustedProxies'], function (string $trustedProxy) {
                return '*' !== $trustedProxy;
            });
        }

        $this->decorated->boot($runtimeConfiguration);
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
