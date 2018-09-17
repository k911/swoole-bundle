<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Command;

use App\Bundle\SwooleBundle\Functions\ServerUtils;
use App\Bundle\SwooleBundle\Server\HttpServer;
use App\Bundle\SwooleBundle\Server\HttpServerConfiguration;
use App\Bundle\SwooleBundle\Server\HttpServerFactory;
use App\Bundle\SwooleBundle\Server\Runtime\BootManager;
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
    private $server;
    private $serverFactory;
    private $serverConfiguration;
    private $kernel;
    private $bootManager;

    /**
     * @param HttpServer              $server
     * @param HttpServerFactory       $serverFactory
     * @param HttpServerConfiguration $serverConfiguration
     * @param KernelInterface         $kernel
     * @param BootManager             $bootManager
     */
    public function __construct(
        HttpServer $server,
        HttpServerFactory $serverFactory,
        HttpServerConfiguration $serverConfiguration,
        KernelInterface $kernel,
        BootManager $bootManager
    ) {
        $this->server = $server;
        $this->serverFactory = $serverFactory;
        $this->serverConfiguration = $serverConfiguration;
        $this->kernel = $kernel;
        $this->bootManager = $bootManager;

        parent::__construct();
    }

    /**
     * @throws \Assert\AssertionFailedException
     *
     * @return string
     */
    private function getDefaultPublicDir(): string
    {
        return $this->serverConfiguration->hasPublicDir() ? $this->serverConfiguration->getPublicDir() : \dirname($this->kernel->getRootDir()).'/public';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Assert\AssertionFailedException
     */
    protected function configure(): void
    {
        $this->setName('swoole:server:profile')
            ->setDescription('Handles specified amount of requests to a local swoole server. Useful for profiling.')
            ->addArgument('requests', InputArgument::REQUIRED, 'Number of requests to handle by the server')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Host name to listen to.')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Range 0-65535. When 0 random available port is chosen.')
            ->addOption('serve-static', 's', InputOption::VALUE_NONE, 'Enables serving static content from public directory')
            ->addOption('public-dir', null, InputOption::VALUE_REQUIRED, 'Public directory', $this->getDefaultPublicDir());
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
        $this->ensureXdebugDisabled();

        $io = new SymfonyStyle($input, $output);

        $socket = $this->serverConfiguration->getDefaultSocket();
        $socket = $socket
            ->withPort((int) ($input->getOption('port') ?? $socket->port()))
            ->withHost((string) ($input->getOption('host') ?? $socket->host()));

        $this->serverConfiguration->changeDefaultSocket($socket);

        if (\filter_var($input->getOption('serve-static'), FILTER_VALIDATE_BOOLEAN)) {
            $this->serverConfiguration->enableServingStaticFiles($input->getOption('public-dir'));
        }

        $requestLimit = (int) $input->getArgument('requests');
        if ($requestLimit <= 0) {
            throw new InvalidArgumentException('Request limit must be greater than 0');
        }

        $runtimeConfiguration = [
            'symfonyStyle' => $io,
            'requestLimit' => $requestLimit,
            'trustedHosts' => ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_HOSTS']),
            'trustedProxies' => ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_PROXIES']),
        ];

        if (\in_array('*', $runtimeConfiguration['trustedProxies'], true)) {
            $runtimeConfiguration['trustAllProxies'] = true;
            $runtimeConfiguration['trustedProxies'] = \array_filter($runtimeConfiguration['trustedProxies'], function (string $trustedProxy): bool {
                return '*' !== $trustedProxy;
            });
        }

        $this->server->attach($this->serverFactory->make(
            $this->serverConfiguration->getDefaultSocket(),
            $this->serverConfiguration->getRunningMode()
        ));
        $this->bootManager->boot($runtimeConfiguration);

        $io->success(\sprintf('Swoole HTTP Server started on http://%s', $this->serverConfiguration->getDefaultSocket()->addressPort()));
        $this->printServerConfiguration($io, $runtimeConfiguration);

        // TODO: Remove or improve before release
        if ($this->kernel->isDebug()) {
            dump($this->serverConfiguration, $this->serverConfiguration->getSwooleSettings());
        }

        if ($this->server->start()) {
            $io->success('Swoole HTTP Server has been successfully shutdown.');
        } else {
            $io->error('Failure during starting Swoole HTTP Server.');
        }
    }

    /**
     * Xdebug must be disabled when using swoole due to possibility of core dump.
     */
    private function ensureXdebugDisabled(): void
    {
        $xdebug = new XdebugHandler('swoole');
        $xdebug->check();
        unset($xdebug);
    }

    /**
     * @param SymfonyStyle $io
     * @param array        $runtimeConfiguration
     *
     * @throws \Assert\AssertionFailedException
     */
    private function printServerConfiguration(SymfonyStyle $io, array $runtimeConfiguration): void
    {
        $rows = [
            ['env', $this->kernel->getEnvironment()],
            ['debug', \var_export($this->kernel->isDebug(), true)],
            ['worker_count', $this->serverConfiguration->getWorkerCount()],
            ['memory_limit', ServerUtils::formatBytes(ServerUtils::getMaxMemory())],
            ['request_limit', $runtimeConfiguration['requestLimit'] > 0 ? $runtimeConfiguration['requestLimit'] : -1],
            ['trusted_hosts', \implode(', ', $runtimeConfiguration['trustedHosts'])],
        ];

        if (isset($runtimeConfiguration['trustAllProxies'])) {
            $rows[] = ['trusted_proxies', '*'];
        } else {
            $rows[] = ['trusted_proxies', \implode(', ', $runtimeConfiguration['trustedProxies'])];
        }

        if ($this->serverConfiguration->hasPublicDir()) {
            $rows[] = ['public_dir', $this->serverConfiguration->getPublicDir()];
        }

        $io->table(['Configuration', 'Values'], $rows);
    }
}
