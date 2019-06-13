<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\Command\Beta;

use Assert\Assertion;
use function K911\Swoole\call_unaccessible_static_method;
use K911\Swoole\Server\Api\ApiServerRequestHandler;
use K911\Swoole\Server\Config\EventCallbacks;
use K911\Swoole\Server\Config\Listener;
use K911\Swoole\Server\Config\ListenerConfig;
use K911\Swoole\Server\Config\Listeners;
use K911\Swoole\Server\Config\ServerConfig;
use K911\Swoole\Server\Config\Socket;
use K911\Swoole\Server\LifecycleHandler\SigIntHandler;
use K911\Swoole\Server\RequestHandler\RequestHandlerInterface;
use K911\Swoole\Server\ServerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class ServerRunCommand extends Command
{
    private $parameterBag;
    private $requestHandler;
    private $sigIntHandler;
    private $apiServerRequestHandler;
    /**
     * @var ServerInterface
     */
    private $server;
    /**
     * @var ServerConfig
     */
    private $serverConfig;
    /**
     * @var Listeners
     */
    private $serverListeners;
    /**
     * @var EventCallbacks
     */
    private $serverEventCallbacks;

    public function __construct(
        ServerInterface $server,
        ServerConfig $serverConfig,
        Listeners $serverListeners,
        EventCallbacks $serverEventCallbacks,
        ParameterBagInterface $parameterBag,
        SigIntHandler $sigIntHandler,
        ApiServerRequestHandler $apiServerRequestHandler,
        RequestHandlerInterface $requestHandler
    ) {
        $this->requestHandler = $requestHandler;
        $this->sigIntHandler = $sigIntHandler;
        $this->parameterBag = $parameterBag;
        $this->apiServerRequestHandler = $apiServerRequestHandler;
        $this->server = $server;
        $this->serverConfig = $serverConfig;
        $this->serverListeners = $serverListeners;
        $this->serverEventCallbacks = $serverEventCallbacks;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Test');

        $mainSocket = $this->serverListeners->mainSocket();

        $this
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Host name to bind to. To bind to any host, use: 0.0.0.0', $mainSocket->host())
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Listen for Swoole HTTP Server on this port, when 0 random available port is chosen', $mainSocket->port())
            ->addOption('reactor', null, InputOption::VALUE_NONE, 'Enables reactor mode')
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Assert\AssertionFailedException
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exitCode = 0;
        $io = new SymfonyStyle($input, $output);

        $apiSocket = new Socket('0.0.0.0', 9200, 'tcp_dualstack', false);
        $apiListenerConfig = new ListenerConfig();
        $apiListenerConfig->enableHttp2Protocol();
        $apiListenerEventsCallbacks = new EventCallbacks();
        $apiListenerEventsCallbacks->registerHttpRequestHandler($this->apiServerRequestHandler);
        $apiListener = new Listener($apiSocket, $apiListenerConfig, $apiListenerEventsCallbacks);

        /** @var bool $reactorModeEnabled */
        $reactorModeEnabled = $input->getOption('reactor');

        if ($reactorModeEnabled) {
            $this->serverConfig->enableReactorMode();
        }

        /** @var string $host */
        $host = $input->getOption('host');

        /** @var string $port */
        $port = $input->getOption('port');

        Assertion::string($host, 'Host must be a string.');
        Assertion::numeric($port, 'Port must be a number.');

        $this->serverListeners->changeMainSocket(
            $this->serverListeners->mainSocket()
                ->withHost($host)
                ->withPort((int) $port)
        );

        $this->serverListeners->addListeners(
            $apiListener
        );

        $this->serverEventCallbacks->registerHttpRequestHandler($this->requestHandler);

        if (!$this->serverConfig->reactorEnabled()) {
            $this->serverEventCallbacks->registerServerStartHandler($this->sigIntHandler);
        }

//        dd($this->serverEventCallbacks);

        $lifecycleEvents = [
            EventCallbacks::EVENT_SERVER_START,
            EventCallbacks::EVENT_SERVER_SHUTDOWN,
            EventCallbacks::EVENT_MANAGER_START,
            EventCallbacks::EVENT_MANAGER_STOP,
            EventCallbacks::EVENT_WORKER_START,
            EventCallbacks::EVENT_WORKER_STOP,
            EventCallbacks::EVENT_WORKER_EXIT,
            EventCallbacks::EVENT_WORKER_ERROR,
        ];

        $logger = new Logger('test', [new StreamHandler('test.log')]);

        foreach ($lifecycleEvents as $eventName) {
            $this->serverEventCallbacks->register($eventName, function () use (&$logger, $eventName): void {
                $logger->debug($eventName, [
                    'pid' => \getmypid(),
                    'event' => $eventName,
                ]);
            });
        }

        // At this point swoole server is created by factory
        $serverInfo = $this->server->info();
        dump($serverInfo);

        $printConfiguration = \array_merge([
            'address' => $this->serverListeners->mainSocket()->hostPort(),
            'server class' => $serverInfo['class'],
            'running mode' => $this->serverConfig->runningMode(),
            'process id' => \getmypid(),
        ], $this->serverConfig->config());

        $io->table([
            'Configuration',
            'Value',
        ], \array_map(function (string $key, $value): array {
            return [\str_replace('_', ' ', $key), call_unaccessible_static_method(Assertion::class, 'stringify', $value)];
        }, \array_keys($printConfiguration), $printConfiguration));

        // ----

        $io->comment('Quit the server with CONTROL-C.');

        if ($this->server->start()) {
            $io->newLine();
            $io->success('Swoole Server has been successfully shutdown.');
        } else {
            $io->error('Failure during starting Swoole Server.');
        }

        return $exitCode;
    }
}
