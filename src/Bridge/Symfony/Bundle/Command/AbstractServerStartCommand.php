<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\Command;

use Assert\Assertion;
use K911\Swoole\Common\XdebugHandler\XdebugHandler;
use function K911\Swoole\decode_string_as_set;
use function K911\Swoole\format_bytes;
use function K911\Swoole\get_max_memory;
use K911\Swoole\Server\Configurator\ConfiguratorInterface;
use K911\Swoole\Server\HttpServer;
use K911\Swoole\Server\HttpServerConfiguration;
use K911\Swoole\Server\HttpServerFactory;
use K911\Swoole\Server\Runtime\BootableInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractServerStartCommand extends Command
{
    protected $parameterBag;

    private $server;
    private $bootManager;
    private $serverConfiguration;
    private $serverConfigurator;

    /**
     * @param HttpServer              $server
     * @param HttpServerConfiguration $serverConfiguration
     * @param ConfiguratorInterface   $serverConfigurator
     * @param ParameterBagInterface   $parameterBag
     * @param BootableInterface       $bootManager
     */
    public function __construct(
        HttpServer $server,
        HttpServerConfiguration $serverConfiguration,
        ConfiguratorInterface $serverConfigurator,
        ParameterBagInterface $parameterBag,
        BootableInterface $bootManager
    ) {
        $this->server = $server;
        $this->bootManager = $bootManager;
        $this->parameterBag = $parameterBag;
        $this->serverConfigurator = $serverConfigurator;
        $this->serverConfiguration = $serverConfiguration;

        parent::__construct();
    }

    /**
     * @throws \Assert\AssertionFailedException
     *
     * @return string
     */
    private function getDefaultPublicDir(): string
    {
        return $this->serverConfiguration->hasPublicDir() ? $this->serverConfiguration->getPublicDir() : $this->parameterBag->get('kernel.project_dir').'/public';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Assert\AssertionFailedException
     */
    protected function configure(): void
    {
        $defaultSocket = $this->serverConfiguration->getDefaultSocket();
        $this->addOption('host', null, InputOption::VALUE_REQUIRED, 'Host name to listen to.', $defaultSocket->host())
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Range 0-65535. When 0 random available port is chosen.', $defaultSocket->port())
            ->addOption('serve-static', 's', InputOption::VALUE_NONE, 'Enables serving static content from public directory.')
            ->addOption('public-dir', null, InputOption::VALUE_REQUIRED, 'Public directory', $this->getDefaultPublicDir())
            ->addOption('trusted-hosts', null, InputOption::VALUE_REQUIRED, 'Trusted hosts', $this->parameterBag->get('swoole.http_server.trusted_hosts'))
            ->addOption('trusted-proxies', null, InputOption::VALUE_REQUIRED, 'Trusted proxies', $this->parameterBag->get('swoole.http_server.trusted_proxies'));
    }

    private function ensureXdebugDisabled(SymfonyStyle $io): void
    {
        $xdebugHandler = new XdebugHandler();
        if (!$xdebugHandler->shouldRestart()) {
            return;
        }

        if ($xdebugHandler->canBeRestarted()) {
            $restartedProcess = $xdebugHandler->prepareRestartedProcess();
            $xdebugHandler->forwardSignals($restartedProcess);

            $io->note('Restarting command without Xdebug..');
            $io->comment(\sprintf(
                "%s\n%s",
                'Swoole is incompatible with Xdebug. Check https://github.com/swoole/swoole-src/issues/1681 for more information.',
                \sprintf('Set environment variable "%s=1" to use it anyway.', $xdebugHandler->allowXdebugEnvName())
            ));

            $restartedProcess->start();

            foreach ($restartedProcess as $processOutput) {
                echo $processOutput;
            }

            exit($restartedProcess->getExitCode());
        }

        $io->warning(\sprintf(
            "Xdebug is enabled! Command could not be restarted automatically due to lack of \"pcntl\" extension.\nPlease either disable Xdebug or set environment variable \"%s=1\" to disable this message.",
            $xdebugHandler->allowXdebugEnvName()
        ));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @throws \Assert\AssertionFailedException
     */
    final protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->ensureXdebugDisabled($io);

        $this->prepareServerConfiguration($this->serverConfiguration, $input);

        if ($this->server->isRunning()) {
            $io->error('Swoole HTTP Server is already running');
            exit(1);
        }

        $server = HttpServerFactory::make(
            $this->serverConfiguration->getDefaultSocket(),
            $this->serverConfiguration->getRunningMode()
        );
        $this->serverConfigurator->configure($server);
        $this->server->attach($server);

        // TODO: Lock server configuration here
//        $this->serverConfiguration->lock();

        $runtimeConfiguration = ['symfonyStyle' => $io] + $this->prepareRuntimeConfiguration($this->serverConfiguration, $input);
        $this->bootManager->boot($runtimeConfiguration);

        $io->success(\sprintf('Swoole HTTP Server started on http://%s', $this->serverConfiguration->getDefaultSocket()->addressPort()));
        $io->table(['Configuration', 'Values'], $this->prepareConfigurationRowsToPrint($this->serverConfiguration, $runtimeConfiguration));

        $this->startServer($this->serverConfiguration, $this->server, $io);

        return 0;
    }

    /**
     * @param HttpServerConfiguration $serverConfiguration
     * @param InputInterface          $input
     *
     * @throws \Assert\AssertionFailedException
     */
    protected function prepareServerConfiguration(HttpServerConfiguration $serverConfiguration, InputInterface $input): void
    {
        $port = $input->getOption('port');
        $host = $input->getOption('host');
        Assertion::numeric($port, 'Port must be numeric');
        Assertion::string($host, 'Host must be string');

        $socket = $serverConfiguration->getDefaultSocket()
            ->withPort((int) $port)
            ->withHost($host);

        $serverConfiguration->changeDefaultSocket($socket);

        if (\filter_var($input->getOption('serve-static'), FILTER_VALIDATE_BOOLEAN)) {
            $publicDir = $input->getOption('public-dir');
            Assertion::string($publicDir, 'Public dir must be a valid path');
            $serverConfiguration->enableServingStaticFiles($publicDir);
        }
    }

    /**
     * @param HttpServerConfiguration $serverConfiguration
     * @param InputInterface          $input
     *
     * @throws \Assert\AssertionFailedException
     *
     * @return array
     */
    protected function prepareRuntimeConfiguration(HttpServerConfiguration $serverConfiguration, InputInterface $input): array
    {
        $trustedHosts = $input->getOption('trusted-hosts');
        $trustedProxies = $input->getOption('trusted-proxies');
        $runtimeConfiguration['trustedHosts'] = $this->decodeSet($trustedHosts);
        $runtimeConfiguration['trustedProxies'] = $this->decodeSet($trustedProxies);

        Assertion::isArray($runtimeConfiguration['trustedProxies']);
        if (\in_array('*', $runtimeConfiguration['trustedProxies'], true)) {
            $runtimeConfiguration['trustAllProxies'] = true;
            $runtimeConfiguration['trustedProxies'] = \array_filter($runtimeConfiguration['trustedProxies'], function (string $trustedProxy): bool {
                return '*' !== $trustedProxy;
            });
        }

        return $runtimeConfiguration;
    }

    /**
     * @param mixed $set
     *
     * @throws \Assert\AssertionFailedException
     *
     * @return array
     */
    private function decodeSet($set): array
    {
        if (\is_string($set)) {
            return decode_string_as_set($set);
        }

        Assertion::isArray($set);

        if (1 === \count($set)) {
            return decode_string_as_set($set[0]);
        }

        return $set;
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
            ['env', $this->parameterBag->get('kernel.environment')],
            ['debug', \var_export($this->parameterBag->get('kernel.debug'), true)],
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

    /**
     * @param HttpServerConfiguration $serverConfiguration
     * @param HttpServer              $server
     * @param SymfonyStyle            $io
     *
     * @throws \Assert\AssertionFailedException
     */
    protected function startServer(HttpServerConfiguration $serverConfiguration, HttpServer $server, SymfonyStyle $io): void
    {
        $io->comment('Quit the server with CONTROL-C.');

        if ($server->start()) {
            $io->newLine();
            $io->success('Swoole HTTP Server has been successfully shutdown.');
        } else {
            $io->error('Failure during starting Swoole HTTP Server.');
        }
    }
}
