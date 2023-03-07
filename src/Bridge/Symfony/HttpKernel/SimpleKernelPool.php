<?php

declare(strict_types=1);

/*
 * @author    mfris
 * @copyright PIXELFEDERATION s.r.o.
 * @license   Internal use only
 */

namespace K911\Swoole\Bridge\Symfony\HttpKernel;

use Symfony\Component\HttpKernel\KernelInterface;

final class SimpleKernelPool implements KernelPoolInterface
{
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function boot(): void
    {
        $this->kernel->boot();
    }

    public function get(): KernelInterface
    {
        return $this->kernel;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function return(KernelInterface $kernel): void
    {
        // no need to be implemented
    }
}
