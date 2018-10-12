<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\Command;

use K911\Swoole\Server\HttpServer;
use K911\Swoole\Server\HttpServerConfiguration;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use function K911\Swoole\get_object_property;

final class ServerStartCommand extends AbstractServerStartCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('swoole:server:start')
            ->setDescription('Runs a local Swoole HTTP server in background.')
            ->addOption('pid_file', null, InputOption::VALUE_REQUIRED, 'Pid file', $this->parameterBag->get('kernel.project_dir').'/var/swoole.pid');

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

        $this->closeSymfonyStyle($io);

        $server->start();
    }

    private function closeSymfonyStyle(SymfonyStyle $io): void
    {
        /** @var ConsoleOutput $consoleOutput */
        $consoleOutput = get_object_property($io, 'output', OutputStyle::class);

        $this->closeConsoleOutput($consoleOutput);
    }

    /**
     * Prevents usage of php://stdout or php://stderr while running in background.
     *
     * @param ConsoleOutput $output
     */
    private function closeConsoleOutput(ConsoleOutput $output): void
    {
        \fclose($output->getStream());

        /** @var StreamOutput $streamOutput */
        $streamOutput = $output->getErrorOutput();

        $streamOutput->setVerbosity(PHP_INT_MIN);
        \fclose($streamOutput->getStream());
    }
}
