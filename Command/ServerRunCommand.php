<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Command;

use App\Bundle\SwooleBundle\Functions\ServerUtils;
use App\Bundle\SwooleBundle\Server\HttpServer;
use App\Bundle\SwooleBundle\Server\HttpServerConfiguration;
use App\Bundle\SwooleBundle\Server\RequestHandlerInterface;
use Composer\XdebugHandler\XdebugHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

final class ServerRunCommand extends Command
{
    private $kernel;
    private $server;
    private $configuration;
    private $driver;

    /**
     * @param KernelInterface         $kernel
     * @param HttpServer              $server
     * @param HttpServerConfiguration $configuration
     * @param RequestHandlerInterface $driver
     */
    public function __construct(KernelInterface $kernel, HttpServer $server, HttpServerConfiguration $configuration, RequestHandlerInterface $driver)
    {
        $this->kernel = $kernel;
        $this->server = $server;
        $this->driver = $driver;
        $this->configuration = $configuration;

        parent::__construct();
    }

    /**
     * @throws \Assert\AssertionFailedException
     *
     * @return string
     */
    private function getDefaultPublicDir(): string
    {
        return $this->configuration->hasPublicDir() ? $this->configuration->getPublicDir() : \dirname($this->kernel->getRootDir()).'/public';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Assert\AssertionFailedException
     */
    protected function configure(): void
    {
        $this->setName('swoole:server:run')
            ->setDescription('Runs a local swoole http server')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Host name to listen to')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Range 0-65535. When 0 random available port is chosen')
            ->addOption('serve-static', 's', InputOption::VALUE_NONE, 'Enables serving static content from public directory')
            ->addOption('public-dir', null, InputOption::VALUE_REQUIRED, 'Public directory', $this->getDefaultPublicDir());
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @throws \Assert\AssertionFailedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $xdebug = new XdebugHandler('swoole');
        $xdebug->check();
        unset($xdebug);

        $io = new SymfonyStyle($input, $output);

        $this->configuration->changeSocket(
            (string) ($input->getOption('host') ?? $this->configuration->getHost()),
            (int) ($input->getOption('port') ?? $this->configuration->getPort())
        );

        if (\filter_var($input->getOption('serve-static'), FILTER_VALIDATE_BOOLEAN)) {
            $this->configuration->enableServingStaticFiles($input->getOption('public-dir'));
        }

        $this->driver->boot([
            'trustedHosts' => ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_HOSTS']),
            'trustedProxies' => ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_PROXIES']),
        ]);

        $this->server->setup($this->configuration);

        $rows = [
            ['env', $this->kernel->getEnvironment()],
            ['debug', \var_export($this->kernel->isDebug(), true)],
            ['worker_count', $this->configuration->getWorkerCount()],
            ['memory_limit', ServerUtils::formatBytes(ServerUtils::getMaxMemory())],
        ];

        if ($this->configuration->hasPublicDir()) {
            $rows[] = ['public_dir', $this->configuration->getPublicDir()];
        }

        $io->success(\sprintf('Swoole HTTP Server started on http://%s:%d', $this->configuration->getHost(), $this->configuration->getPort()));
        $io->table(['Configuration', 'Values'], $rows);

        if ($this->server->start($this->driver)) {
            $io->success('Swoole HTTP Server has been successfully shutdown.');
        } else {
            $io->error('Failure during starting Swoole HTTP Server.');
        }
    }
}
