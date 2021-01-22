<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpFoundation;

use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

final class SwooleRequestResponseContextManager
{
    private const REQUEST_ATTR_KEY = 'swoole_request';
    private const RESPONSE_ATTR_KEY = 'swoole_response';

    public function attachRequestResponseAttributes(
        HttpFoundationRequest $request,
        SwooleRequest $swooleRequest,
        SwooleResponse $swooleResponse
    ): void {
        $request->attributes->set(static::REQUEST_ATTR_KEY, $swooleRequest);
        $request->attributes->set(static::RESPONSE_ATTR_KEY, $swooleResponse);
    }

    public function findRequest(
        HttpFoundationRequest $request
    ): SwooleRequest {
        return $request->attributes->get(static::REQUEST_ATTR_KEY);
    }

    public function findResponse(
        HttpFoundationRequest $request
    ): SwooleResponse {
        return $request->attributes->get(static::RESPONSE_ATTR_KEY);
    }
}
