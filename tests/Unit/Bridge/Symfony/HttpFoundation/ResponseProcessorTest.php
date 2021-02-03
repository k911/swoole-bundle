<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Bridge\Symfony\HttpFoundation;

use K911\Swoole\Bridge\Symfony\HttpFoundation\ResponseProcessor;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class ResponseProcessorTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @var ResponseProcessor
     */
    protected $responseProcessor;

    /**
     * @var null|HttpFoundationResponse
     */
    protected $symfonyResponse;

    /**
     * @var null|ObjectProphecy|SwooleResponse
     */
    protected $swooleResponse;

    protected function setUp(): void
    {
        $this->responseProcessor = new ResponseProcessor();
        $this->swooleResponse = $this->prophesize(SwooleResponse::class);
    }

    public function testProcess(): void
    {
        $content = 'success';
        $this->symfonyResponse = new HttpFoundationResponse(
            $content,
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
        $this->swooleResponse->end($content)->shouldBeCalled();
        $this->responseProcessor->process($this->symfonyResponse, $swooleResponse);
    }
}
