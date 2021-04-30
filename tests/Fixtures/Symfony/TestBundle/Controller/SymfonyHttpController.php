<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

final class SymfonyHttpController
{
    /**
     * @Route(
     *     methods={"GET"},
     *     path="/http/request/uri"
     * )
     *
     * @see \K911\Swoole\Tests\Feature\SymfonyHttpRequestContainsRequestUriTest::testWhetherCurrentSymfonyHttpRequestContainsRequestUri()
     */
    public function getRequestUri(Request $currentRequest): JsonResponse
    {
        return new JsonResponse(['requestUri' => $currentRequest->getRequestUri()], 200);
    }

    /**
     * @Route(
     *     methods={"GET"},
     *     path="/http/request/streamed-uri"
     * )
     */
    public function getStreamedRequestUri(Request $currentRequest): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($currentRequest): void {
            $response = ['requestUri' => $currentRequest->getRequestUri()];
            echo \json_encode($response);
        });
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
