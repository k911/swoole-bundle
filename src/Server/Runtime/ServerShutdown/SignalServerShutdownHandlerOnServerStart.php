<?php

declare(strict_types=1);

namespace K911\Swoole\Server\Runtime\ServerShutdown;

use K911\Swoole\Process\Signal\Signal;
use K911\Swoole\Process\Signal\SignalHandlerInterface;
use K911\Swoole\Server\LifecycleHandler\ServerStartHandlerInterface;
use Swoole\Server;

/**
 * Register SIGINT handler upon server startup when running in "process" mode only.
 */
final class SignalServerShutdownHandlerOnServerStart implements ServerStartHandlerInterface
{
    private ?ServerStartHandlerInterface $decorated;
    private SignalHandlerInterface $signalHandler;

    public function __construct(SignalHandlerInterface $signalHandler, ?ServerStartHandlerInterface $decorated = null)
    {
        $this->decorated = $decorated;
        $this->signalHandler = $signalHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Server $server): void
    {
        // Note: SIGTERM is already registered by Swoole itself
        $this->signalHandler->register([$server, 'shutdown'], Signal::int());

        if ($this->decorated instanceof ServerStartHandlerInterface) {
            $this->decorated->handle($server);
        }
    }
}
