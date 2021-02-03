<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Bridge\Symfony\HttpFoundation;

use K911\Swoole\Bridge\Symfony\HttpFoundation\ResponseHeadersAndStatusProcessor;
use K911\Swoole\Bridge\Symfony\HttpFoundation\ResponseProcessorInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class ResponseHeadersAndStatusProcessorTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @var null|ObjectProphecy|ResponseHeadersAndStatusProcessor
     */
    protected $responseProcessor;

    /**
     * @var null|ObjectProphecy|SwooleResponse
     */
    protected $swooleResponse;

    protected function setUp(): void
    {
        $this->swooleResponse = $this->prophesize(SwooleResponse::class);
        $decoratedProcessor = $this->prophesize(ResponseProcessorInterface::class);
        $decoratedProcessor
            ->process(Argument::type(HttpFoundationResponse::class), $this->swooleResponse->reveal())
            ->shouldBeCalled()
        ;
        $this->responseProcessor = new ResponseHeadersAndStatusProcessor($decoratedProcessor->reveal());
    }

    public function testProcess(): void
    {
        $symfonyResponse = new HttpFoundationResponse(
            'success',
            200,
            [
                'Vary' => [
                    'Content-Type',
                    'Authorization',
                    'Origin',
                ],
            ]
        );

        $swooleResponse = $this->swooleResponse->reveal();
        $this->swooleResponse->status(200)->shouldBeCalled();
        foreach ($symfonyResponse->headers->allPreserveCaseWithoutCookies() as $name => $values) {
            $this->swooleResponse->header($name, \implode(', ', $values))->shouldBeCalled();
        }
        $this->responseProcessor->process($symfonyResponse, $swooleResponse);
    }
}
