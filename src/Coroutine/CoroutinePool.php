<?php

declare(strict_types=1);

namespace K911\Swoole\Coroutine;

use Assert\Assertion;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Scheduler;
use Throwable;

/**
 * @internal
 */
final class CoroutinePool
{
    private $coroutines;
    private $coroutinesCount;
    private $results = [];
    private $exceptions = [];
    private $resultsChannel;
    private $started = false;

    public function __construct(Channel $resultsChannel, callable ...$coroutines)
    {
        $this->coroutines = $coroutines;
        $this->coroutinesCount = \count($coroutines);
        $this->resultsChannel = $resultsChannel;
    }

    public static function fromCoroutines(callable ...$coroutines): self
    {
        return new self(new Channel(1), ...$coroutines);
    }

    /**
     * Blocks until all coroutines have been finished.
     */
    public function run(): array
    {
        $this->start();

        // TODO: Create parent exception containing all child exceptions and throw it instead
        if (\count($this->exceptions) > 0) {
            throw $this->exceptions[0];
        }

        return $this->results;
    }

    private function start(): void
    {
        Assertion::false($this->started, 'Single PoolExecutor cannot be run twice.');
        $this->started = true;

        if (self::inCoroutine()) {
            $this->startWaitGroup();

            return;
        }

        $this->startScheduler();
    }

    private function startWaitGroup(): void
    {
        foreach ($this->coroutines as $coroutine) {
            Coroutine::create($this->wrapCoroutine($this->resultsChannel, $coroutine));
        }

        Coroutine::create($this->makeGatherResults());
    }

    private function startScheduler(): void
    {
        $scheduler = new Scheduler();

        foreach ($this->coroutines as $coroutine) {
            $scheduler->add($this->wrapCoroutine($this->resultsChannel, $coroutine));
        }

        $scheduler->add($this->makeGatherResults());
        $scheduler->start();
    }

    private function makeGatherResults(): \Closure
    {
        return function (): void {
            while ($this->coroutinesCount > 0) {
                $result = $this->resultsChannel->pop(-1);
                $outputName = $result instanceof Throwable ? 'exceptions' : 'results';
                $this->{$outputName}[] = $result;
                --$this->coroutinesCount;
            }
        };
    }

    private function wrapCoroutine(Channel $resultChannel, callable $coroutine): \Closure
    {
        return static function () use ($resultChannel, $coroutine): void {
            $result = null;

            try {
                $result = $coroutine() ?? true;
            } catch (\Throwable $exception) {
                $result = $exception;
            }
            $resultChannel->push($result);
        };
    }

    private static function inCoroutine(): bool
    {
        return -1 !== Coroutine::getuid();
    }
}
