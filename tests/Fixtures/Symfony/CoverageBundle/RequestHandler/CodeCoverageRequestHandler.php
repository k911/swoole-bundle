<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\RequestHandler;

use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\Coverage\CodeCoverageManager;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class CodeCoverageRequestHandler implements RequestHandlerInterface
{
    private $decorated;
    private $codeCoverageManager;

    public function __construct(RequestHandlerInterface $decorated, CodeCoverageManager $codeCoverageManager)
    {
        $this->decorated = $decorated;
        $this->codeCoverageManager = $codeCoverageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, Response $response): void
    {
        $testName = $this->codeCoverageManager->generateRandomTestName('test_request');
        $this->codeCoverageManager->start($testName);

        $this->decorated->handle($request, $response);

        $this->codeCoverageManager->finish($testName);
    }
}
