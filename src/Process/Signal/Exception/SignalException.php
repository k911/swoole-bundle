<?php

declare(strict_types=1);

namespace K911\Swoole\Process\Signal\Exception;

use K911\Swoole\Process\Signal\Signal;

final class SignalException extends \RuntimeException
{
    public static function fromThrowable(\Throwable $exception): self
    {
        return new self($exception->getMessage(), $exception->getCode(), $exception);
    }

    public static function fromKillCommand(int $processId, Signal $signal): self
    {
        return new self(\sprintf('Unable to kill process having id "%d" using signal "%s (%d)"', $processId, $signal->name(), $signal->number()));
    }
}
