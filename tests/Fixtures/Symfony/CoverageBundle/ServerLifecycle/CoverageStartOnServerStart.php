<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\ServerLifecycle;

use K911\Swoole\Server\LifecycleHandler\ServerStartHandlerInterface;
use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\Coverage\CodeCoverageManager;
use Swoole\Server;

final class CoverageStartOnServerStart implements ServerStartHandlerInterface
{
    private $codeCoverageManager;
    private $decorated;

    public function __construct(CodeCoverageManager $codeCoverageManager, ?ServerStartHandlerInterface $decorated = null)
    {
        $this->codeCoverageManager = $codeCoverageManager;
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Server $server): void
    {
        $this->codeCoverageManager->start('test_server');

        if ($this->decorated instanceof ServerStartHandlerInterface) {
            $this->decorated->handle($server);
        }
    }
}
