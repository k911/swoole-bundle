<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Command;

use App\Bundle\SwooleBundle\Driver\HttpDriverInterface;
use App\Bundle\SwooleBundle\Server\ServerUtils;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

class ServerProfileCommand extends Command
{
    private $kernel;
    private $server;
    private $driver;

    public function __construct(KernelInterface $kernel, Server $server, HttpDriverInterface $driver)
    {
        parent::__construct();

        $this->kernel = $kernel;
        $this->server = $server;
        $this->driver = $driver;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->setName('swoole:server:profile')
            ->setDescription('Handles specified amount of requests to a local swoole server. Useful for debug or benchmarking.')
            ->addArgument('requests', InputArgument::REQUIRED, 'Number of requests to handle by the server')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Host of the server')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Port of the server')
            ->addOption('enable-static', null, InputOption::VALUE_NONE, 'Enables static files serving');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfRangeException
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

        $staticFilesServingEnabled = (bool) $input->getOption('enable-static');
        $requestLimit = (int) $input->getArgument('requests');
        if ($requestLimit <= 0) {
            throw new InvalidArgumentException('Request limit must be greater than 0');
        }

        if ($staticFilesServingEnabled) {
            $this->server->set([
                'enable_static_handler' => true,
                'document_root' => $this->kernel->getRootDir().'/public',
            ]);
        }

        $this->server->on('request', function (Request $request, Response $response) {
            $this->driver->handle($request, $response);
        });

        $trustedHosts = ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_HOSTS']);
        $trustedProxies = ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_PROXIES']);
        $this->driver->boot([
            'symfonyStyle' => $io,
            'requestLimit' => $requestLimit,
            'trustedHosts' => $trustedHosts,
            'trustedProxies' => $trustedProxies,
        ]);

        $output->writeln(\sprintf('<info>Swoole HTTP Server started on http://%s:%d</info>', $host, $port));

        $rows = [
            ['env', $this->kernel->getEnvironment()],
            ['debug', \var_export($this->kernel->isDebug(), true)],
            ['memory_limit', ServerUtils::formatBytes(ServerUtils::getMaxMemory())],
            ['request_limit', $requestLimit > 0 ? $requestLimit : -1],
            ['trusted_hosts', \implode(', ', $trustedHosts)],
            ['trusted_proxies', \implode(', ', $trustedProxies)],
        ];

        if ($staticFilesServingEnabled) {
            $rows[] = ['document_root', $this->kernel->getRootDir().'/public'];
        }

        $io->newLine();
        $io->table(['Configuration', 'Values'], $rows);

        $this->server->start();
    }
}
