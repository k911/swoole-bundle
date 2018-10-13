<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server;

use Swoole\Server;

final class SwooleServerMock extends Server
{
    public $registeredTick = false;
    public $registeredTickTuple = [];

    public function __construct(bool $taskworker)
    {
        $this->taskworker = $taskworker;
    }

    public function tick($interval, $callback, $param = null): void
    {
        $this->registeredTick = true;
        $this->registeredTickTuple = [$interval, $callback, $param];
    }
}
