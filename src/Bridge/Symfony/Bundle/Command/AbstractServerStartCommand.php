<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\Command;

use Assert\Assertion;
use K911\Swoole\Common\XdebugHandler\XdebugHandler;
use function K911\Swoole\decode_string_as_set;
use function K911\Swoole\format_bytes;
use function K911\Swoole\get_max_memory;
use K911\Swoole\Server\Config\Socket;
use K911\Swoole\Server\Configurator\ConfiguratorInterface;
use K911\Swoole\Server\HttpServer;
use K911\Swoole\Server\HttpServerConfiguration;
use K911\Swoole\Server\HttpServerFactory;
use K911\Swoole\Server\Runtime\BootableInterface;
use Swoole\Http\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

abstract class AbstractServerStartCommand extends Command
{
    /**
     * @var ParameterBagInterface
     */
    protected $parameterBag;

    private $server;
    private $bootManager;
    private $serverConfiguration;
    private $serverConfigurator;

    /**
     * @var bool
     */
    private $testing = false;

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

    public function enableTestMode(): void
    {
        $this->testing = true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Assert\AssertionFailedException
     */
    protected function configure(): void
    {
        $sockets = $this->serverConfiguration->getSockets();
        $serverSocket = $sockets->getServerSocket();
        $this->addOption('host', null, InputOption::VALUE_REQUIRED, 'Host name to bind to. To bind to any host, use: 0.0.0.0', $serverSocket->host())
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Listen for Swoole HTTP Server on this port, when 0 random available port is chosen', $serverSocket->port())
            ->addOption('serve-static', 's', InputOption::VALUE_NONE, 'Enables serving static content from public directory')
            ->addOption('public-dir', null, InputOption::VALUE_REQUIRED, 'Public directory', $this->getDefaultPublicDir())
            ->addOption('trusted-hosts', null, InputOption::VALUE_REQUIRED, 'Trusted hosts', $this->parameterBag->get('swoole.http_server.trusted_hosts'))
            ->addOption('trusted-proxies', null, InputOption::VALUE_REQUIRED, 'Trusted proxies', $this->parameterBag->get('swoole.http_server.trusted_proxies'))
            ->addOption('api', null, InputOption::VALUE_NONE, 'Enable API Server')
            ->addOption('api-port', null, InputOption::VALUE_REQUIRED, 'Listen for API Server on this port', $this->parameterBag->get('swoole.http_server.api.port'))
        ;
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

        $swooleServer = $this->makeSwooleHttpServer();
        $this->serverConfigurator->configure($swooleServer);
        $this->server->attach($swooleServer);

        // TODO: Lock server configuration here
//        $this->serverConfiguration->lock();

        $runtimeConfiguration = ['symfonyStyle' => $io] + $this->prepareRuntimeConfiguration($this->serverConfiguration, $input);
        $this->bootManager->boot($runtimeConfiguration);

        $sockets = $this->serverConfiguration->getSockets();
        $serverSocket = $sockets->getServerSocket();
        $io->success(\sprintf('Swoole HTTP Server started on http://%s', $serverSocket->addressPort()));
        if ($sockets->hasApiSocket()) {
            $io->success(\sprintf('API Server started on http://%s', $sockets->getApiSocket()->addressPort()));
        }
        $io->table(['Configuration', 'Values'], $this->prepareConfigurationRowsToPrint($this->serverConfiguration, $runtimeConfiguration));

        if ($this->testing) {
            return 0;
        }

        $this->startServer($this->serverConfiguration, $this->server, $io);

        return 0;
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    protected function prepareServerConfiguration(HttpServerConfiguration $serverConfiguration, InputInterface $input): void
    {
        $sockets = $serverConfiguration->getSockets();

        /** @var string $port */
        $port = $input->getOption('port');

        /** @var string $host */
        $host = $input->getOption('host');

        Assertion::numeric($port, 'Port must be a number.');
        Assertion::string($host, 'Host must be a string.');

        $newServerSocket = $sockets->getServerSocket()
            ->withPort((int) $port)
            ->withHost($host)
        ;

        $sockets->changeServerSocket($newServerSocket);

        if ((bool) $input->getOption('api') || $sockets->hasApiSocket()) {
            /** @var string $apiPort */
            $apiPort = $input->getOption('api-port');
            Assertion::numeric($apiPort, 'Port must be a number.');

            $sockets->changeApiSocket(new Socket('0.0.0.0', (int) $apiPort));
        }

        if (\filter_var($input->getOption('serve-static'), FILTER_VALIDATE_BOOLEAN)) {
            $publicDir = $input->getOption('public-dir');
            Assertion::string($publicDir, 'Public dir must be a valid path');
            $serverConfiguration->enableServingStaticFiles($publicDir);
        }
    }

    /**
     * @throws \Assert\AssertionFailedException
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
     * Rows produced by this function will be printed on server startup in table with following form:
     * | Configuration | Value |.
     *
     * @throws \Assert\AssertionFailedException
     */
    protected function prepareConfigurationRowsToPrint(HttpServerConfiguration $serverConfiguration, array $runtimeConfiguration): array
    {
        $rows = [
            ['env', $this->parameterBag->get('kernel.environment')],
            ['debug', \var_export($this->parameterBag->get('kernel.debug'), true)],
            ['running_mode', $serverConfiguration->getRunningMode()],
            ['worker_count', $serverConfiguration->getWorkerCount()],
            ['reactor_count', $serverConfiguration->getReactorCount()],
            ['worker_max_request', $serverConfiguration->getMaxRequest()],
            ['worker_max_request_grace', $serverConfiguration->getMaxRequestGrace()],
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

    /**
     * @throws \Assert\AssertionFailedException
     */
    private function getDefaultPublicDir(): string
    {
        return $this->serverConfiguration->hasPublicDir() ? $this->serverConfiguration->getPublicDir() : $this->parameterBag->get('kernel.project_dir').'/public';
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

            if ($this->testing) {
                return;
            }

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

    private function makeSwooleHttpServer(): Server
    {
        $sockets = $this->serverConfiguration->getSockets();
        $serverSocket = $sockets->getServerSocket();

        return HttpServerFactory::make(
            $serverSocket,
            $this->serverConfiguration->getRunningMode(),
            ...($sockets->hasApiSocket() ? [$sockets->getApiSocket()] : [])
        );
    }

    /**
     * @param mixed $set
     *
     * @throws \Assert\AssertionFailedException
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
}
