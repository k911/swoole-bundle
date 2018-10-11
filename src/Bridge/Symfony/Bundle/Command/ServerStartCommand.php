<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\Command;

use K911\Swoole\Server\HttpServer;
use K911\Swoole\Server\HttpServerConfiguration;
use K911\Swoole\Server\HttpServerFactory;
use K911\Swoole\Server\Runtime\BootManager;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use function K911\Swoole\get_object_property;
use function K911\Swoole\replace_object_property;

final class ServerStartCommand extends AbstractServerStartCommand
{
    /**
     * @var null|LoggerInterface
     */
    private $logger;

    public function __construct(
        HttpServer $server,
        HttpServerFactory $serverFactory,
        HttpServerConfiguration $serverConfiguration,
        ParameterBagInterface $parameterBag,
        BootManager $bootManager,
        ?LoggerInterface $logger = null
    )
    {
        parent::__construct($server, $serverFactory, $serverConfiguration, $parameterBag, $bootManager);
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('swoole:server:start')
            ->setDescription('Runs a local Swoole HTTP server in background.')
            ->addOption('pid_file', null, InputOption::VALUE_REQUIRED, 'Pid file', $this->parameterBag->get('kernel.project_dir') . '/var/swoole.pid');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareServerConfiguration(HttpServerConfiguration $serverConfiguration, InputInterface $input): void
    {
        /** @var string|null $pidFile */
        $pidFile = $input->getOption('pid_file');
        $serverConfiguration->daemonize($pidFile);

        parent::prepareServerConfiguration($serverConfiguration, $input);
    }

    /**
     * {@inheritdoc}
     */
    protected function startServer(HttpServerConfiguration $serverConfiguration, HttpServer $server, SymfonyStyle $io): void
    {
        if (!$serverConfiguration->existsPidFile() && !\touch($serverConfiguration->getPidFile())) {
            throw new RuntimeException(\sprintf('Could not create pid file "%s".', $serverConfiguration->getPid()));
        }

        /** @var ConsoleOutput $consoleOutput */
        $consoleOutput = &get_object_property($io, 'output', OutputStyle::class);

        /** @var resource $stream */
        $stream = &get_object_property($consoleOutput, 'stream', StreamOutput::class);

        \fclose($stream);

        if ($this->logger instanceof Logger) {
            $this->removeConsoleHandlerLogger($this->logger);
        }

        unset($this->logger);

//        $this->forceCloseAllStdIoStreams();

//        $this->getApplication()->get()

//        dd(get_resources('stream'));

        $server->start();
    }

    private function removeConsoleHandlerLogger(Logger $logger): void
    {
        $handlers = [];
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof ConsoleHandler) {
                /** @var StreamOutput $streamOutput */
                $streamOutput = &get_object_property($handler, 'output', ConsoleHandler::class);

                /** @var resource $stream */
                $stream = &get_object_property($streamOutput, 'stream', StreamOutput::class);
                \fclose($stream);

                $stream = tmpfile();
                replace_object_property($streamOutput, 'stream', $stream, StreamOutput::class);
            } else {
                $handlers[] = $handler;
            }
        }
        $logger->setHandlers($handlers);
    }

    private function forceCloseAllStdIoStreams(): void
    {
        dump('d');
        foreach (\get_resources('stream') as $stream) {
            if ('STDIO' === \stream_get_meta_data($stream)['stream_type']) {
                \fclose($stream);
            }
        }
        dump('d');
    }
}
