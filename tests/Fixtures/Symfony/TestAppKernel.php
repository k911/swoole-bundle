<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony;

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
        yield new SwooleBundle();
        yield new TestBundle();

        if ('cov' === $this->environment) {
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
    }

    private function getVarDir(): string
    {
        return $this->getProjectDir().'/var';
    }

    /**
     * {@inheritdoc}
     */
    public function getProjectDir(): string
    {
        return __DIR__.'/app';
    }
}
