<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpFoundation;

use Swoole\Http\Request as SwooleRequest;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

interface RequestFactoryInterface
{
    public function make(SwooleRequest $request): HttpFoundationRequest;
}
