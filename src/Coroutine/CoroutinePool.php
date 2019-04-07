<?php

declare(strict_types=1);

namespace K911\Swoole\Coroutine;

use Assert\Assertion;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Event;
use Throwable;

/**
 * @internal
 */
final class CoroutinePool
{
    private $coroutines;
    private $coroutinesCount;
    private $results = [];
    private $resultsChannel;
    private $exception;
    private $started = false;

    public function __construct(Channel $resultsChannel, callable ...$coroutines)
    {
        $this->coroutines = $coroutines;
        $this->coroutinesCount = \count($coroutines);
        $this->resultsChannel = $resultsChannel;
    }

    public static function fromCoroutines(callable ...$coroutines): self
    {
        $count = \count($coroutines);
        $channel = new Channel($count);

        return new self($channel, ...$coroutines);
    }

    /**
     * Blocks until all coroutines have been finished.
     */
    public function run(): array
    {
        Assertion::false($this->started, 'Single PoolExecutor cannot be run twice.');
        $this->started = true;

        foreach ($this->coroutines as $coroutine) {
            $this->startCoroutine($this->wrapPushResultToChannel($this->resultsChannel, $coroutine));
        }

        $this->waitForCompletion();

        if ($this->exception instanceof Throwable) {
            throw $this->exception;
        }

        return $this->results;
    }

    private function startCoroutine(callable $coroutine): void
    {
        Assertion::false(\extension_loaded('xdebug'), 'Swoole Coroutine is incompatible with Xdebug extension. Please disable it and try again.');

        \go($coroutine);
    }

    private function wrapPushResultToChannel(Channel $channel, callable $coroutine): callable
    {
        return function () use ($coroutine, $channel): void {
            $result = null;
            try {
                $result = $coroutine() ?? true;
            } catch (\Throwable $exception) {
                $result = $exception;
            }
            $channel->push($result);
        };
    }

    private function waitForCompletion(): void
    {
        if (self::inCoroutine()) {
            $this->writeResults();

            return;
        }

        $this->startCoroutine([$this, 'writeResults']);
        Event::wait();
    }

    private static function inCoroutine(): bool
    {
        return -1 !== Coroutine::getuid();
    }

    private function writeResults(): void
    {
        while ($this->coroutinesCount > 0) {
            $result = $this->resultsChannel->pop();

            if ($result instanceof Throwable) {
                $this->exception = $result;
                break;
            }

            $this->results[] = $result;
            --$this->coroutinesCount;
        }
    }
}
