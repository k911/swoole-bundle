<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\Command;

use function K911\Swoole\get_object_property;
use K911\Swoole\Server\HttpServer;
use K911\Swoole\Server\HttpServerConfiguration;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ServerStartCommand extends AbstractServerStartCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Run Swoole HTTP server in the background.')
            ->addOption('pid-file', null, InputOption::VALUE_REQUIRED, 'Pid file', $this->parameterBag->get('kernel.project_dir').'/var/swoole.pid');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareServerConfiguration(HttpServerConfiguration $serverConfiguration, InputInterface $input): void
    {
        /** @var string|null $pidFile */
        $pidFile = $input->getOption('pid-file');
        $serverConfiguration->daemonize($pidFile);

        parent::prepareServerConfiguration($serverConfiguration, $input);
    }

    /**
     * {@inheritdoc}
     */
    protected function startServer(HttpServerConfiguration $serverConfiguration, HttpServer $server, SymfonyStyle $io): void
    {
        $pidFile = $serverConfiguration->getPidFile();
        if (!\touch($pidFile) || !\is_writable($pidFile)) {
            throw new RuntimeException(\sprintf('Could not access or create pid file "%s".', $serverConfiguration->getPidFile()));
        }

        $this->closeSymfonyStyle($io);

        $server->start();
    }

    private function closeSymfonyStyle(SymfonyStyle $io): void
    {
        $output = get_object_property($io, 'output', OutputStyle::class);
        if ($output instanceof ConsoleOutput) {
            $this->closeConsoleOutput($output);
        } elseif ($output instanceof StreamOutput) {
            $this->closeStreamOutput($output);
        }
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

        $this->closeStreamOutput($streamOutput);
    }

    private function closeStreamOutput(StreamOutput $output): void
    {
        $output->setVerbosity(PHP_INT_MIN);
        \fclose($output->getStream());
    }
}
