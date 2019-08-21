<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server;

use Swoole\Http\Server;

final class SwooleHttpServerMock extends Server
{
    public $registeredEvent = false;
    public $registeredEventPair = [];
    private static $instance;

    private function __construct()
    {
        parent::__construct('localhost', 31999);
    }

    public function on($event, $callback): void
    {
        $this->registeredEvent = true;
        $this->registeredEventPair = [$event, $callback];
    }

    public static function make(): self
    {
        if (!self::$instance instanceof self) {
            self::$instance = new self();
        }

        self::$instance->clean();

        return self::$instance;
    }

    private function clean(): void
    {
        $this->registeredEvent = false;
        $this->registeredEventPair = [];
    }
}
