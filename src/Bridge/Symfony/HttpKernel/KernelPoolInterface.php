<?php

declare(strict_types=1);

/*
 * @author    mfris
 * @copyright PIXELFEDERATION s.r.o.
 * @license   Internal use only
 */

namespace K911\Swoole\Bridge\Symfony\HttpKernel;

use Symfony\Component\HttpKernel\KernelInterface;

interface KernelPoolInterface
{
    public function boot(): void;

    public function get(): KernelInterface;

    public function return(KernelInterface $kernel);
}
