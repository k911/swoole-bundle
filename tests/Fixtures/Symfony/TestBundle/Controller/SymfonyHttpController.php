<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
}
