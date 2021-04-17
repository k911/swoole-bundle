<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\TaskHandler;

use K911\Swoole\Server\TaskHandler\TaskHandlerInterface;
use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\Coverage\CodeCoverageManager;
use Swoole\Server;

final class CodeCoverageTaskHandler implements TaskHandlerInterface
{
    private $decorated;
    private $codeCoverageManager;

    public function __construct(TaskHandlerInterface $decorated, CodeCoverageManager $codeCoverageManager)
    {
        $this->decorated = $decorated;
        $this->codeCoverageManager = $codeCoverageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Server $server, int $taskId, int $fromId, $data): void
    {
        $testName = $this->codeCoverageManager->generateRandomTestName(\sprintf('test_task_%d_%d', $taskId, $fromId));
        $this->codeCoverageManager->start($testName);

        $this->decorated->handle($server, $taskId, $fromId, $data);

        $this->codeCoverageManager->finish($testName);
    }
}
