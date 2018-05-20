<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Command;

use App\Bundle\SwooleBundle\Driver\ConsoleDebugDriver;
use App\Bundle\SwooleBundle\Server\ServerUtils;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

class ServerRunCommand extends Command
{
    private $kernel;
    private $server;

    /**
     * @param KernelInterface $kernel
     * @param Server          $server
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(KernelInterface $kernel, Server $server)
    {
        parent::__construct();

        $this->kernel = $kernel;
        $this->server = $server;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->setName('swoole:server:run')
            ->setDescription('Runs a local swoole server')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Host of the server')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Port of the server')
            ->addOption('enable-static', null, InputOption::VALUE_NONE, 'Enables static files serving');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $io = new SymfonyStyle($input, $output);

        $host = (string) ($input->getOption('host') ?? $this->server->host);
        $port = (int) ($input->getOption('port') ?? $this->server->port);
        $this->server->host = $host;
        $this->server->port = $port;

//        $profilingEnabled = (bool) $input->getOption('enable-profiling');
        $staticFilesServingEnabled = (bool) $input->getOption('enable-static');
        $driver = new ConsoleDebugDriver($this->kernel, $output, false);

        if ($staticFilesServingEnabled) {
            $this->server->set([
                'enable_static_handler' => true,
                'document_root' => $this->kernel->getRootDir().'/public',
            ]);
        }

        $this->server->on('request', function (Request $request, Response $response) use ($driver) {
            $driver->handle($request, $response);
        });

        $trustedHosts = ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_HOSTS']);
        $trustedProxies = ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_PROXIES']);
        $driver->boot($trustedHosts, $trustedProxies);

        $rows = [
            ['env', $this->kernel->getEnvironment()],
            ['debug', \var_export($this->kernel->isDebug(), true)],
//            ['profiling', \var_export($profilingEnabled, true)],
            ['memory_limit', ServerUtils::formatBytes(ServerUtils::getMaxMemory())],
            ['trusted_hosts', \implode(', ', $trustedHosts)],
            ['trusted_proxies', \implode(', ', $trustedProxies)],
        ];

        if ($staticFilesServingEnabled) {
            $rows[] = ['document_root', $this->kernel->getRootDir().'/public'];
        }

        $output->writeln(\sprintf('<info>Swoole HTTP Server started on http://%s:%d</info>', $host, $port));
        $io->newLine();
        $io->table(['Configuration', 'Values'], $rows);

        $this->server->start();
    }
}
