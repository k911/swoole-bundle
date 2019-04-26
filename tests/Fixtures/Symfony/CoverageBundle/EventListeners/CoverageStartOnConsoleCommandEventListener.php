<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\EventListeners;

use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\Coverage\CodeCoverageManager;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

final class CoverageStartOnConsoleCommandEventListener
{
    private $coverageManager;

    public function __construct(CodeCoverageManager $coverageManager)
    {
        $this->coverageManager = $coverageManager;
    }

    public function __invoke(ConsoleCommandEvent $commandEvent): void
    {
        $slug = str_replace(['-', ':'], '_', $commandEvent->getCommand()->getName());
        $this->coverageManager->start(sprintf('test_cmd_%s', $slug));
    }
}
