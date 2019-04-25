<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use Exception;
use Generator;
use K911\Swoole\Bridge\Symfony\Bundle\SwooleBundle;
use K911\Swoole\Tests\Fixtures\Symfony\CoverageBundle\CoverageBundle;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\TestBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class TestAppKernel extends Kernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTENSIONS = '.{php,xml,yaml,yml}';

    private $coverageEnabled;

    public function __construct(string $environment, bool $debug)
    {
        if ('_cov' === \mb_substr($environment, -4, 4)) {
            $environment = \mb_substr($environment, 0, -4);
            $this->coverageEnabled = true;
        } elseif ('cov' === $environment) {
            $this->coverageEnabled = true;
        } else {
            $this->coverageEnabled = false;
        }

        parent::__construct($environment, $debug);
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return $this->getVarDir().'/cache/'.$this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir(): string
    {
        return $this->getVarDir().'/log';
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles(): Generator
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new MonologBundle();
        yield new DoctrineBundle();
        yield new DoctrineMigrationsBundle();
        yield new SwooleBundle();
        yield new TestBundle();

        if ($this->coverageEnabled) {
            yield new CoverageBundle();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Config\Exception\FileLoaderLoadException
     */
    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $routes->import('app/routing.yml');
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->setParameter('bundle.root_dir', \dirname(__DIR__, 3));

        $confDir = $this->getProjectDir().'/config';
        $loader->load($confDir.'/*'.self::CONFIG_EXTENSIONS, 'glob');
        if (\is_dir($confDir.'/'.$this->environment)) {
            $loader->load($confDir.'/'.$this->environment.'/**/*'.self::CONFIG_EXTENSIONS, 'glob');
        }

        if ($this->coverageEnabled && 'cov' !== $this->environment) {
            $loader->load($confDir.'/cov/**/*'.self::CONFIG_EXTENSIONS, 'glob');
        }
    }

    private function getVarDir(): string
    {
        return $this->getProjectDir().'/var/'.$this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectDir(): string
    {
        return __DIR__.'/app';
    }
}
