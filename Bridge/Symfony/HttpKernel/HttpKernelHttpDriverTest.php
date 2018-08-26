<?php

declare(strict_types=1);

namespace App\Tests\Bundle\SwooleBundle\Bridge\Symfony\HttpKernel;

use App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation\RequestFactoryInterface;
use App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation\ResponseProcessorInterface;
use App\Bundle\SwooleBundle\Bridge\Symfony\HttpKernel\HttpKernelHttpServerDriver;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

class HttpKernelHttpDriverTest extends TestCase
{
    /**
     * @var HttpKernelHttpServerDriver
     */
    private $httpDriver;

    /**
     * @var ResponseProcessorInterface|ObjectProphecy
     */
    private $responseProcessor;

    /**
     * @var RequestFactoryInterface|ObjectProphecy
     */
    private $requestFactoryProphecy;

    /**
     * @var KernelInterface|TerminableInterface|ObjectProphecy
     */
    private $kernelProphecy;

    protected function setUp(): void
    {
        $this->kernelProphecy = $this->prophesize(KernelInterface::class);
        $this->requestFactoryProphecy = $this->prophesize(RequestFactoryInterface::class);
        $this->responseProcessor = $this->prophesize(ResponseProcessorInterface::class);

        /** @var KernelInterface $kernelMock */
        $kernelMock = $this->kernelProphecy->reveal();
        /** @var RequestFactoryInterface $requestFactoryMock */
        $requestFactoryMock = $this->requestFactoryProphecy->reveal();
        /** @var ResponseProcessorInterface $responseProcessorMock */
        $responseProcessorMock = $this->responseProcessor->reveal();

        $this->httpDriver = new HttpKernelHttpServerDriver($kernelMock, $requestFactoryMock, $responseProcessorMock);
    }

    public function testBoot(): void
    {
        $configuration = [
            'trustedHosts' => ['127.0.0.1', 'localhost'],
            'trustedProxies' => ['192.168.1.0/24', '73.41.22.1', 'varnish'],
            'trustedHeaderSet' => Request::HEADER_X_FORWARDED_AWS_ELB,
        ];

        $this->kernelProphecy->boot()->shouldBeCalled();

        $this->httpDriver->boot($configuration);

        $this->assertSame(['{127.0.0.1}i', '{localhost}i'], Request::getTrustedHosts());

        $this->assertSame($configuration['trustedProxies'], Request::getTrustedProxies());
        $this->assertSame($configuration['trustedHeaderSet'], Request::getTrustedHeaderSet());
    }

    /**
     * @throws \Exception
     */
    public function testHandleNonTerminable(): void
    {
        $swooleRequest = new SwooleRequest();
        $swooleResponse = new SwooleResponse();

        $httpFoundationResponse = new HttpFoundationResponse();
        $httpFoundationRequest = new HttpFoundationRequest();

        $this->requestFactoryProphecy->make($swooleRequest)->willReturn($httpFoundationRequest)->shouldBeCalled();
        $this->kernelProphecy->handle($httpFoundationRequest)->willReturn($httpFoundationResponse)->shouldBeCalled();
        $this->responseProcessor->process($httpFoundationResponse, $swooleResponse)->shouldBeCalled();

        $this->httpDriver->handle($swooleRequest, $swooleResponse);
    }

    /**
     * @throws \Exception
     */
    public function testHandleTerminable(): void
    {
        $this->setUpTerminableKernel();

        $swooleRequest = new SwooleRequest();
        $swooleResponse = new SwooleResponse();

        $httpFoundationResponse = new HttpFoundationResponse();
        $httpFoundationRequest = new HttpFoundationRequest();

        $this->requestFactoryProphecy->make($swooleRequest)->willReturn($httpFoundationRequest)->shouldBeCalled();
        $this->kernelProphecy->handle($httpFoundationRequest)->willReturn($httpFoundationResponse)->shouldBeCalled();
        $this->responseProcessor->process($httpFoundationResponse, $swooleResponse)->shouldBeCalled();
        $this->kernelProphecy->terminate($httpFoundationRequest, $httpFoundationResponse)->shouldBeCalled();

        $this->httpDriver->handle($swooleRequest, $swooleResponse);
    }

    private function setUpTerminableKernel(): void
    {
        $this->kernelProphecy = $this->prophesize(KernelInterface::class)->willImplement(TerminableInterface::class);

        /** @var KernelInterface $kernelMock */
        $kernelMock = $this->kernelProphecy->reveal();
        /** @var RequestFactoryInterface $requestFactoryMock */
        $requestFactoryMock = $this->requestFactoryProphecy->reveal();
        /** @var ResponseProcessorInterface $responseProcessorMock */
        $responseProcessorMock = $this->responseProcessor->reveal();

        $this->httpDriver = new HttpKernelHttpServerDriver($kernelMock, $requestFactoryMock, $responseProcessorMock);
    }
}
