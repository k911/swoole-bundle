<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Bridge\Symfony\HttpFoundation;

use K911\Swoole\Bridge\Symfony\HttpFoundation\StreamedResponseProcessor;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamedResponseProcessorTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @var StreamedResponseProcessor
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

    /**
     * @var int
     */
    private $bufferSize;

    protected function setUp(): void
    {
        $this->bufferSize = 10;
        $this->responseProcessor = new StreamedResponseProcessor($this->bufferSize);
        $this->swooleResponse = $this->prophesize(SwooleResponse::class);
    }

    public function testProcess(): void
    {
        $expectedContentLength = $this->bufferSize * 3;
        $this->symfonyResponse = new StreamedResponse(
            static function () use ($expectedContentLength): void {
                for ($i = 0; $i < $expectedContentLength; ++$i) {
                    echo 'A';
                }
            },
            200,
        );

        $remainingContentLength = $expectedContentLength;
        while ($remainingContentLength > 0) {
            $bufferedContentLength = \min($this->bufferSize, $remainingContentLength);
            $this->swooleResponse->write(\str_repeat('A', $bufferedContentLength))->shouldBeCalled();
            $remainingContentLength -= $bufferedContentLength;
        }
        $this->swooleResponse->end()->shouldBeCalled();
        $swooleResponse = $this->swooleResponse->reveal();
        $this->responseProcessor->process($this->symfonyResponse, $swooleResponse);
    }
}
