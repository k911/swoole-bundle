<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\HttpFoundation;

use Swoole\Http\Request as SwooleRequest;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

final class CloudFrontRequestFactory implements RequestFactoryInterface
{
    private $decorated;

    public function __construct(RequestFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/header-caching.html#header-caching-web-protocol
     */
    public function make(SwooleRequest $request): HttpFoundationRequest
    {
        $httpFoundationRequest = $this->decorated->make($request);
        if ($httpFoundationRequest->headers->has('cloudfront_forwarded_proto')) {
            $httpFoundationRequest->headers->set('x_forwarded_proto', $httpFoundationRequest->headers->get('cloudfront_forwarded_proto'));
        }

        return $httpFoundationRequest;
    }
}
