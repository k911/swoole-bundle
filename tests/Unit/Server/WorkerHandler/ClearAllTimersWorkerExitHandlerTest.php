<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server\WorkerHandler;

use K911\Swoole\Server\WorkerHandler\ClearAllTimersWorkerExitHandler;
use K911\Swoole\Server\WorkerHandler\NoOpWorkerExitHandler;
use K911\Swoole\Server\WorkerHandler\WorkerExitHandlerInterface;
use K911\Swoole\Tests\Unit\Server\IntMother;
use K911\Swoole\Tests\Unit\Server\SwooleServerMock;
use PHPUnit\Framework\TestCase;
use Swoole\Timer;

final class ClearAllTimersWorkerExitHandlerTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;

    private NoOpWorkerExitHandler $noOpWorkerExitHandler;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|WorkerExitHandlerInterface
     */
    private $workerExitHandlerProphecy;

    private ClearAllTimersWorkerExitHandler $clearAllTimersWorkerExitHandler;

    protected function setUp(): void
    {
        $this->workerExitHandlerProphecy = $this->prophesize(WorkerExitHandlerInterface::class);

        $this->clearAllTimersWorkerExitHandler = new ClearAllTimersWorkerExitHandler($this->workerExitHandlerProphecy->reveal());
    }

    public function testClearAllTimersAfterHandle(): void
    {
        $timerId = Timer::tick(1000, function (): void {});
        self::assertFalse(Timer::info($timerId)['removed']);

        $serverMock = SwooleServerMock::make();
        $workerId = IntMother::random();

        $this->workerExitHandlerProphecy->handle($serverMock, $workerId)
            ->shouldBeCalled()
        ;

        $this->clearAllTimersWorkerExitHandler->handle($serverMock, $workerId);

        self::assertNull(Timer::info($timerId));
    }
}
