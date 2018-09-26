<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation;

use Swoole\Http\Request as SwooleRequest;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

interface RequestFactoryInterface
{
    /**
     * @param \Swoole\Http\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function make(SwooleRequest $request): HttpFoundationRequest;
}
