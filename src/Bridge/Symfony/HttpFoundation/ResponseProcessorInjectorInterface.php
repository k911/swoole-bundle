<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpFoundation;

use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

interface ResponseProcessorInjectorInterface
{
    public const ATTR_KEY_RESPONSE_PROCESSOR = 'swoole_streamed_response_processor';

    public function injectProcessor(HttpFoundationRequest $request, SwooleResponse $swooleResponse): void;
}
