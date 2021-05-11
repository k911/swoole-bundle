<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\Coverage;

use Assert\Assertion;
use DateTimeImmutable;
use SebastianBergmann\CodeCoverage\CodeCoverage;
use SebastianBergmann\CodeCoverage\Report\PHP;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class CodeCoverageManager
{
    /**
     * @var string
     */
    private $currentTestName;

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
    private $started = false;

    public function __construct(ParameterBagInterface $parameterBag, CodeCoverage $codeCoverage, PHP $writer)
    {
        $this->codeCoverage = $codeCoverage;

        $this->enabled = $parameterBag->get('coverage.enabled');

        $this->coveragePath = $parameterBag->get('coverage.path');

        $this->initalizeCodeCoverage($parameterBag, $codeCoverage);

        $this->writer = $writer;
    }

    public function start(string $testName): void
    {
        Assertion::notEmpty($testName);

        if (!$this->enabled || $this->started) {
            return;
        }

        $this->codeCoverage->start($this->currentTestName = $testName);
        $this->started = true;
    }

    public function finish(?string $fileName = null, ?string $path = null): void
    {
        if (!$this->enabled || !$this->started) {
            return;
        }

        $this->started = false;
        $this->codeCoverage->stop();
        $timestamp = (new DateTimeImmutable())->getTimestamp();
        $fileName = \sprintf('%s_%s.cov', $fileName ?? $this->currentTestName, $timestamp);

        $this->writer->process($this->codeCoverage, \sprintf('%s/%s', $path ?? $this->coveragePath, $fileName));
        $this->codeCoverage->clear();
    }

    public function generateRandomTestName(string $testNamePrefix): string
    {
        return \sprintf('%s_%s', $testNamePrefix, \bin2hex(\random_bytes(8)));
    }

    private function initalizeCodeCoverage(ParameterBagInterface $parameterBag, CodeCoverage $codeCoverage): void
    {
        $coverageDir = $parameterBag->has('coverage.dir') ?
            $parameterBag->get('coverage.dir') :
            \sprintf('%s/%s', \dirname(__DIR__, 5), 'src');

        $codeCoverage->filter()->includeDirectory($coverageDir);
    }
}
