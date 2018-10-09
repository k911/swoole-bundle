<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server;

use Swoole\Http\Server;

final class SwooleServerOnEventSpy extends Server
{
    public $registered;
    public $registeredEventCallbackPair;

    public function __construct()
    {
        $this->registered = false;
        $this->registeredEventCallbackPair = [];
    }

    public function on($event, $callback): void
    {
        $this->registered = true;
        $this->registeredEventCallbackPair = [$event, $callback];
    }
}
