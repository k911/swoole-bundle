<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Upscale\Blackfire;

use K911\Swoole\Server\Configurator\ConfiguratorInterface;
use Swoole\Http\Server;
use Upscale\Swoole\Blackfire\Profiler;

final class WithProfiler implements ConfiguratorInterface
{
    /**
     * @var Profiler
     */
    private $profiler;

    public function __construct()
    {
        $this->profiler = new Profiler();
    }

    /**
     * {@inheritdoc}
     */
    public function configure(Server $server): void
    {
        $this->profiler->instrument($server);
    }
}
