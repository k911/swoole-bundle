<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server;

use Swoole\Http\Server;

final class SwooleHttpServerMock extends Server
{
    public $registeredEvent = false;
    public $registeredEventPair = [];

    public function __construct()
    {
    }

    public function on($event, $callback): void
    {
        $this->registeredEvent = true;
        $this->registeredEventPair = [$event, $callback];
    }
}
