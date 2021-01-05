<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Bridge\Symfony\HttpFoundation;

use K911\Swoole\Bridge\Symfony\HttpFoundation\CloudFrontRequestFactory;
use K911\Swoole\Bridge\Symfony\HttpFoundation\RequestFactoryInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Swoole\Http\Request as SwooleRequest;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

class CloudFrontRequestFactoryTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @var ObjectProphecy|RequestFactoryInterface
     */
    private $decoratedProphecy;

    /**
     * @var CloudFrontRequestFactory
     */
    private $requestFactory;

    protected function setUp(): void
    {
        $this->decoratedProphecy = $this->prophesize(RequestFactoryInterface::class);

        /** @var RequestFactoryInterface $decoratedMock */
        $decoratedMock = $this->decoratedProphecy->reveal();
        $this->requestFactory = new CloudFrontRequestFactory($decoratedMock);
    }

    public function testHandleNoCloudFrontHeader(): void
    {
        $swooleRequest = new SwooleRequest();
        $httpFoundationRequest = new HttpFoundationRequest();

        $this->decoratedProphecy->make($swooleRequest)->willReturn($httpFoundationRequest)->shouldBeCalled();

        self::assertSame($httpFoundationRequest, $this->requestFactory->make($swooleRequest));
    }

    public function testHandleCloudFrontHeader(): void
    {
        $swooleRequest = new SwooleRequest();
        $httpFoundationRequest = new HttpFoundationRequest([], [], [], [], [], ['HTTP_CLOUDFRONT_FORWARDED_PROTO' => 'https']);

        $this->decoratedProphecy->make($swooleRequest)->willReturn($httpFoundationRequest)->shouldBeCalled();

        self::assertSame($httpFoundationRequest, $this->requestFactory->make($swooleRequest));
        self::assertSame('https', $httpFoundationRequest->headers->get('x_forwarded_proto'));
    }
}
