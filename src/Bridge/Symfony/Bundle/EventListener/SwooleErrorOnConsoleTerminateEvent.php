<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\EventListener;

use Symfony\Component\Console\Event\ConsoleTerminateEvent;

final class SwooleErrorOnConsoleTerminateEvent
{
    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $swooleLastErrorNo = \swoole_last_error();
        if (0 !== $swooleLastErrorNo) {
            echo 'Swoole Error: '.\swoole_strerror($swooleLastErrorNo, 9).\PHP_EOL;
        }
    }
}
