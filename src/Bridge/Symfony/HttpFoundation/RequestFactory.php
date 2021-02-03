<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpFoundation;

use Swoole\Http\Request as SwooleRequest;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

final class RequestFactory implements RequestFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function make(SwooleRequest $request): HttpFoundationRequest
    {
        $server = \array_change_key_case($request->server, \CASE_UPPER);

        // Add formatted headers to server
        foreach ($request->header as $key => $value) {
            $server['HTTP_'.\mb_strtoupper(\str_replace('-', '_', $key))] = $value;
        }

        $queryString = $server['QUERY_STRING'] ?? '';
        $server['REQUEST_URI'] = $server['REQUEST_URI'] ?? '';
        $server['REQUEST_URI'] .= '' !== $queryString ? '?'.$queryString : '';

        return new HttpFoundationRequest(
            $request->get ?? [],
            $request->post ?? [],
            [],
            $request->cookie ?? [],
            $request->files ?? [],
            $server,
            $request->rawContent()
        );
    }
}
