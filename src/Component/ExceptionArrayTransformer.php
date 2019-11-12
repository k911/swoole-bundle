<?php

declare(strict_types=1);

namespace K911\Swoole\Component;

use Throwable;

final class ExceptionArrayTransformer
{
    public function transform(Throwable $exception, string $verbosity = 'default'): array
    {
        switch ($verbosity) {
            case 'trace':
                return $this->transformWithFn($exception, [$this, 'transformFnVerboseWithTrace']);
            case 'verbose':
                return $this->transformWithFn($exception, [$this, 'transformFnVerbose']);
            default:
                return $this->transformWithFn($exception, [$this, 'transformFnDefault']);
        }
    }

    private function transformWithFn(Throwable $exception, callable $transformer): array
    {
        $data = $transformer($exception);

        $previous = $exception->getPrevious();

        if ($previous instanceof Throwable) {
            $data['previous'] = $transformer($previous);
        }

        return $data;
    }

    private function transformFnDefault(Throwable $exception): array
    {
        return [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
        ];
    }

    private function transformFnVerbose(Throwable $exception): array
    {
        return [
            'class' => \get_class($exception),
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];
    }

    private function transformFnVerboseWithTrace(Throwable $exception): array
    {
        return [
            'class' => \get_class($exception),
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => \array_map(function (array $trace): array {
                $trace['args'] = \array_key_exists('args', $trace) ? $this->transformTraceArgs($trace['args']) : null;

                return $trace;
            }, $exception->getTrace()),
        ];
    }

    private function transformTraceArgs(array $args): array
    {
        return \array_map(function ($arg): string {
            return \is_object($arg) ? \get_class($arg) : \gettype($arg);
        }, $args);
    }
}
