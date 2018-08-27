<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Command;

use App\Bundle\SwooleBundle\Functions\ServerUtils;
use App\Bundle\SwooleBundle\Server\HttpServer;
use App\Bundle\SwooleBundle\Server\HttpServerConfiguration;
use App\Bundle\SwooleBundle\Server\RequestHandlerInterface;
use Composer\XdebugHandler\XdebugHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

final class ServerProfileCommand extends Command
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
        parent::__construct();

        $this->kernel = $kernel;
        $this->server = $server;
        $this->driver = $driver;
        $this->configuration = $configuration;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->setName('swoole:server:profile')
            ->setDescription('Handles specified amount of requests to a local swoole server. Useful for debug or benchmarking.')
            ->addArgument('requests', InputArgument::REQUIRED, 'Number of requests to handle by the server')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Host of the server')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Port of the server')
            ->addOption('enable-static', null, InputOption::VALUE_NONE, 'Enables static files serving');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfRangeException
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

        $host = (string) ($input->getOption('host') ?? $this->configuration->getHost());
        $port = (int) ($input->getOption('port') ?? $this->configuration->getPort());

        $this->configuration->changeSocket($host, $port);

        if ((bool) $input->getOption('enable-static')) {
            $this->configuration->enableServingStaticFiles(\dirname($this->kernel->getRootDir()).'/public');
        }

        $requestLimit = (int) $input->getArgument('requests');
        if ($requestLimit <= 0) {
            throw new InvalidArgumentException('Request limit must be greater than 0');
        }

        $trustedHosts = ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_HOSTS']);
        $trustedProxies = ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_PROXIES']);
        $this->driver->boot([
            'symfonyStyle' => $io,
            'requestLimit' => $requestLimit,
            'trustedHosts' => $trustedHosts,
            'trustedProxies' => $trustedProxies,
        ]);

        $rows = [
            ['env', $this->kernel->getEnvironment()],
            ['debug', \var_export($this->kernel->isDebug(), true)],
            ['worker_count', $this->configuration->getWorkerCount()],
            ['memory_limit', ServerUtils::formatBytes(ServerUtils::getMaxMemory())],
            ['request_limit', $requestLimit > 0 ? $requestLimit : -1],
            ['trusted_hosts', \implode(', ', $trustedHosts)],
            ['trusted_proxies', \implode(', ', $trustedProxies)],
        ];

        if ($this->configuration->hasPublicDir()) {
            $rows[] = ['public_dir', $this->configuration->getPublicDir()];
        }

        $io->success(\sprintf('Swoole HTTP Server started on http://%s:%d', $host, $port));
        $io->table(['Configuration', 'Values'], $rows);

        if ($this->kernel->isDebug()) {
            dump($this->configuration);
            dump($this->configuration->getSwooleSettings());
        }

        $this->server->setSymfonyStyle($io);
        $this->server->start($this->driver, $this->configuration);
    }
}
