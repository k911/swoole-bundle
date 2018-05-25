<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Symfony\HttpKernel;

use App\Bundle\SwooleBundle\Driver\DriverInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

final class TrustAllProxiesHttpKernelDriver implements DriverInterface
{
    private $trustAllProxies;
    private $decorated;

    /**
     * @param DriverInterface $decorated
     * @param bool            $trustAllProxiesByDefault true - always trust all proxies,
     *                                                  false - an '*' character must be in trusted proxies set
     */
    public function __construct(DriverInterface $decorated, bool $trustAllProxiesByDefault = false)
    {
        $this->decorated = $decorated;
        $this->trustAllProxies = $trustAllProxiesByDefault;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(array $configuration = []): void
    {
        if (\array_key_exists('trustedProxies', $configuration)) {
            if (!$this->trustAllProxies && \in_array('*', $configuration['trustedProxies'], true)) {
                $this->trustAllProxies = true;
            }

            if ($this->trustAllProxies) {
                unset($configuration['trustedProxies']);
            }
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
