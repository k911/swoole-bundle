<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation;

use Swoole\Http\Request as SwooleRequest;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

final class RequestFactory implements RequestFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function make(SwooleRequest $request): HttpFoundationRequest
    {
        $server = \array_change_key_case($request->server, CASE_UPPER);

        // Add formatted headers to server
        foreach ($request->header as $key => $value) {
            $server['HTTP_'.\mb_strtoupper(\str_replace('-', '_', $key))] = $value;
        }

        $httpFoundationRequest = new HttpFoundationRequest(
            $request->get ?? [],
            $request->post ?? [],
            [], $request->cookie ?? [],
            $request->files ?? [],
            $server,
            $request->rawcontent()
        );

        if (0 === \mb_strpos($httpFoundationRequest->headers->get('content-type', ''), 'application/x-www-form-urlencoded')
            && \in_array(\mb_strtoupper($httpFoundationRequest->server->get('REQUEST_METHOD', 'GET')), ['PUT', 'DELETE', 'PATCH'])
        ) {
            \parse_str($httpFoundationRequest->getContent(), $data);
            $httpFoundationRequest->request = new ParameterBag($data);
        }

        return $httpFoundationRequest;
    }
}
