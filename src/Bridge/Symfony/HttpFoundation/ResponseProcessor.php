<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpFoundation;

use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use function implode;

final class ResponseProcessor implements ResponseProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(HttpFoundationResponse $httpFoundationResponse, SwooleResponse $swooleResponse): void
    {
        $this->processHeader($httpFoundationResponse, $swooleResponse);

        $this->processCookies($httpFoundationResponse, $swooleResponse);

        $swooleResponse->status($httpFoundationResponse->getStatusCode());

        if ($httpFoundationResponse instanceof BinaryFileResponse) {
            $swooleResponse->sendfile($httpFoundationResponse->getFile()->getRealPath());
        } elseif ($httpFoundationResponse instanceof StreamedResponse) {
            $this->processStreamedResponse($httpFoundationResponse, $swooleResponse);
        } else {
            $swooleResponse->end($httpFoundationResponse->getContent());
        }
    }

    private function processHeader(HttpFoundationResponse $httpFoundationResponse, SwooleResponse $swooleResponse)
    {
        foreach ($httpFoundationResponse->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            $swooleResponse->header($name, implode(', ', $values));
        }
    }

    private function processCookies(HttpFoundationResponse $httpFoundationResponse, SwooleResponse $swooleResponse)
    {
        foreach ($httpFoundationResponse->headers->getCookies() as $cookie) {
            $swooleResponse->cookie(
                $cookie->getName(),
                $cookie->getValue() ?? '',
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain() ?? '',
                $cookie->isSecure(),
                $cookie->isHttpOnly(),
                $cookie->getSameSite() ?? ''
            );
        }
    }

    private function processStreamedResponse(HttpFoundationResponse $httpFoundationResponse, SwooleResponse $swooleResponse)
    {
        ob_start(function (string $payload) use ($swooleResponse) {
            if ($payload !== '') {
                $swooleResponse->write($payload);
            }
            return '';
        }, 8192);
        $httpFoundationResponse->sendContent();
        ob_end_clean();
        $swooleResponse->end();
    }
}
