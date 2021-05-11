<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\Command;

use K911\Swoole\Bridge\Symfony\Bundle\Exception\CouldNotCreatePidFileException;
use K911\Swoole\Bridge\Symfony\Bundle\Exception\PidFileNotAccessibleException;
use K911\Swoole\Server\HttpServer;
use K911\Swoole\Server\HttpServerConfiguration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ServerStartCommand extends AbstractServerStartCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Run Swoole HTTP server in the background.')
            ->addOption('pid-file', null, InputOption::VALUE_REQUIRED, 'Pid file', $this->parameterBag->get('kernel.project_dir').'/var/swoole.pid')
        ;

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareServerConfiguration(HttpServerConfiguration $serverConfiguration, InputInterface $input): void
    {
        /** @var null|string $pidFile */
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

        if (!\touch($pidFile)) {
            throw PidFileNotAccessibleException::forFile($pidFile);
        }

        if (!\is_writable($pidFile)) {
            throw CouldNotCreatePidFileException::forPath($pidFile);
        }

        $server->start();
    }
}
