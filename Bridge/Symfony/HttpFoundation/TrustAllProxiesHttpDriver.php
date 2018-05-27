<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation;

use App\Bundle\SwooleBundle\Driver\HttpDriverInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

final class TrustAllProxiesHttpDriver implements HttpDriverInterface
{
    private $decorated;
    private $trustAllProxies;

    public function __construct(HttpDriverInterface $decorated)
    {
        $this->decorated = $decorated;
        $this->trustAllProxies = false;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(array $configuration = []): void
    {
        if (isset($configuration['trustedProxies']) && \in_array('*', $configuration['trustedProxies'], true)) {
            $this->trustAllProxies = true;
            $configuration['trustedProxies'] = \array_filter($configuration['trustedProxies'], function (string $trustedProxy) {
                return '*' !== $trustedProxy;
            });
        }

        $this->decorated->boot($configuration);
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
