<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation;

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
