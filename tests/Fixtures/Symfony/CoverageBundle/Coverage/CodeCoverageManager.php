<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\Coverage;

use DateTimeImmutable;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\PHP;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class CodeCoverageManager
{
    /**
     * @var string
     */
    private $testName;

    /**
     * @var string
     */
    private $coveragePath;

    /**
     * @var bool
     */
    private $enabled;

    private $codeCoverage;
    private $writer;

    private $finished = false;
    private $started = false;

    public function __construct(ParameterBagInterface $parameterBag, CodeCoverage $codeCoverage, PHP $writer)
    {
        $this->codeCoverage = $codeCoverage;

        $this->enabled = $parameterBag->get('coverage.enabled');

        $testName = null;
        if ($parameterBag->has('coverage.test_name')) {
            $testName = $parameterBag->get('coverage.test_name');
        }

        $this->testName = $testName ?? $this->generateTestName();

        $this->coveragePath = $parameterBag->get('coverage.path');

        $this->initalizeCodeCoverage($parameterBag, $codeCoverage);

        $this->writer = $writer;
    }

    public function start(?string $testName = null): void
    {
        if (!$this->enabled || $this->started) {
            return;
        }

        $this->codeCoverage->start($testName ?? $this->testName);
        $this->started = true;

        if ($this->finished) {
            $this->finished = false;
        }
    }

    public function stop(): void
    {
        if (!$this->enabled || $this->finished) {
            return;
        }

        $this->codeCoverage->stop();
        $this->started = false;
    }

    public function finish(?string $fileName = null, ?string $path = null): void
    {
        if (!$this->enabled || $this->finished) {
            return;
        }

        $timestamp = (new DateTimeImmutable())->getTimestamp();
        $fileName = \sprintf('%s_%s.cov', $fileName ?? $this->testName, $timestamp);

        $this->writer->process($this->codeCoverage, \sprintf('%s/%s', $path ?? $this->coveragePath, $fileName));
        $this->codeCoverage->clear();
        $this->finished = true;

        if ($this->started) {
            $this->started = false;
        }
    }

    private function initalizeCodeCoverage(ParameterBagInterface $parameterBag, CodeCoverage $codeCoverage): void
    {
        $coverageDir = $parameterBag->has('coverage.dir') ?
            $parameterBag->get('coverage.dir') :
            \sprintf('%s/%s', \dirname(__DIR__, 5), 'src');

        $codeCoverage->filter()->addDirectoryToWhitelist($coverageDir);
    }

    private function generateTestName(): string
    {
        return \sprintf('cc_test_%s', \bin2hex(\random_bytes(4)));
    }
}
