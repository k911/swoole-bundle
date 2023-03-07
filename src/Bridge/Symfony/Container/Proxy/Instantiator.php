<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Container\Proxy;

use K911\Swoole\Bridge\Symfony\Container\ServicePool;
use Symfony\Component\Filesystem\Filesystem;

final class Instantiator
{
    private Generator $proxyGenerator;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var string
     */
    private $proxyDir;

    /**
     * @var bool
     */
    private $proxyDirExists = false;

    public function __construct(Generator $proxyGenerator, Filesystem $fileSystem, string $proxyDir)
    {
        $this->proxyGenerator = $proxyGenerator;
        $this->fileSystem = $fileSystem;
        $this->proxyDir = $proxyDir;
    }

    public function newInstance(ServicePool $servicePool, string $wrappedSvcClass): object
    {
        $this->ensureProxyDirExists();

        return $this->proxyGenerator->createProxy(
            $servicePool,
            $wrappedSvcClass
        );
    }

    private function ensureProxyDirExists(): void
    {
        if ($this->proxyDirExists) {
            return;
        }

        if ($this->fileSystem->exists($this->proxyDir)) {
            $this->proxyDirExists = true;

            return;
        }

        $this->fileSystem->mkdir($this->proxyDir);
        $this->proxyDirExists = true;
    }
}
