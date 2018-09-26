<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\Command;

use Composer\XdebugHandler\XdebugHandler;
use K911\Swoole\Server\HttpServer;
use K911\Swoole\Server\HttpServerConfiguration;
use K911\Swoole\Server\HttpServerFactory;
use K911\Swoole\Server\Runtime\BootManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;
use function K911\Swoole\decode_string_as_set;
use function K911\Swoole\format_bytes;
use function K911\Swoole\get_max_memory;

abstract class AbstractServerStartCommand extends Command
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
        $this->addOption('host', null, InputOption::VALUE_REQUIRED, 'Host name to listen to.')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Range 0-65535. When 0 random available port is chosen.')
            ->addOption('serve-static', 's', InputOption::VALUE_NONE, 'Enables serving static content from public directory.')
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
    final protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $this->ensureXdebugDisabled();

        $io = new SymfonyStyle($input, $output);

        $this->prepareServerConfiguration($this->serverConfiguration, $input);

        $this->server->attach($this->serverFactory->make(
            $this->serverConfiguration->getDefaultSocket(),
            $this->serverConfiguration->getRunningMode()
        ));

        // TODO: Lock server configuration here
//        $this->serverConfiguration->lock();

        $runtimeConfiguration = ['symfonyStyle' => $io] + $this->prepareRuntimeConfiguration($this->serverConfiguration, $input);
        $this->bootManager->boot($runtimeConfiguration);

        $io->success(\sprintf('Swoole HTTP Server started on http://%s', $this->serverConfiguration->getDefaultSocket()->addressPort()));
        $io->table(['Configuration', 'Values'], $this->prepareConfigurationRowsToPrint($this->serverConfiguration, $runtimeConfiguration));

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
     * @param HttpServerConfiguration $serverConfiguration
     * @param InputInterface          $input
     *
     * @throws \Assert\AssertionFailedException
     */
    protected function prepareServerConfiguration(HttpServerConfiguration $serverConfiguration, InputInterface $input): void
    {
        $socket = $serverConfiguration->getDefaultSocket();
        $socket = $socket
            ->withPort((int) ($input->getOption('port') ?? $socket->port()))
            ->withHost((string) ($input->getOption('host') ?? $socket->host()));

        $serverConfiguration->changeDefaultSocket($socket);

        if (\filter_var($input->getOption('serve-static'), FILTER_VALIDATE_BOOLEAN)) {
            $serverConfiguration->enableServingStaticFiles($input->getOption('public-dir'));
        }
    }

    /**
     * @param HttpServerConfiguration $serverConfiguration
     * @param InputInterface          $input
     *
     * @return array
     */
    protected function prepareRuntimeConfiguration(HttpServerConfiguration $serverConfiguration, InputInterface $input): array
    {
        $runtimeConfiguration['trustedHosts'] = decode_string_as_set($_SERVER['APP_TRUSTED_HOSTS']);
        $runtimeConfiguration['trustedProxies'] = decode_string_as_set($_SERVER['APP_TRUSTED_PROXIES']);

        if (\in_array('*', $runtimeConfiguration['trustedProxies'], true)) {
            $runtimeConfiguration['trustAllProxies'] = true;
            $runtimeConfiguration['trustedProxies'] = \array_filter($runtimeConfiguration['trustedProxies'], function (string $trustedProxy): bool {
                return '*' !== $trustedProxy;
            });
        }

        return $runtimeConfiguration;
    }

    /**
     * Rows produced by this function will be printed on server startup in table with following form:
     * | Configuration | Value |.
     *
     * @param HttpServerConfiguration $serverConfiguration
     * @param array                   $runtimeConfiguration
     *
     * @throws \Assert\AssertionFailedException
     *
     * @return array
     */
    protected function prepareConfigurationRowsToPrint(HttpServerConfiguration $serverConfiguration, array $runtimeConfiguration): array
    {
        $rows = [
            ['env', $this->kernel->getEnvironment()],
            ['debug', \var_export($this->kernel->isDebug(), true)],
            ['worker_count', $serverConfiguration->getWorkerCount()],
            ['memory_limit', format_bytes(get_max_memory())],
            ['trusted_hosts', \implode(', ', $runtimeConfiguration['trustedHosts'])],
        ];

        if (isset($runtimeConfiguration['trustAllProxies'])) {
            $rows[] = ['trusted_proxies', '*'];
        } else {
            $rows[] = ['trusted_proxies', \implode(', ', $runtimeConfiguration['trustedProxies'])];
        }

        if ($this->serverConfiguration->hasPublicDir()) {
            $rows[] = ['public_dir', $serverConfiguration->getPublicDir()];
        }

        return $rows;
    }
}
