<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Command;

use App\Bundle\SwooleBundle\Driver\ConsoleDebugDriver;
use App\Bundle\SwooleBundle\Server\Counter;
use App\Bundle\SwooleBundle\Server\ServerUtils;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

class SwooleServerHandle extends Command
{
    private $requestCounter;

    private $kernel;

    /**
     * @param KernelInterface $kernel
     * @param Counter         $counter
     *
     * @throws \Symfony\Component\Console\Exception\LogicException
     */
    public function __construct(KernelInterface $kernel, Counter $counter)
    {
        parent::__construct();

        $this->requestCounter = $counter;
        $this->kernel = $kernel;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure(): void
    {
        $this->setName('swoole:server:handle')
            ->setDescription('Handles specified amount of requests to a local swoole server. Useful for debug or benchmarking.')
            ->addArgument('requests', InputArgument::REQUIRED, 'Number of requests to handle by the server')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Host of the server', '127.0.0.1')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Port of the server', 9501)
            ->addOption('enable-profiling', null, InputOption::VALUE_NONE, 'Enables server profiling')
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
        $host = $input->getOption('host');
        $port = (int) $input->getOption('port');
        $server = new Server($host, $port, SWOOLE_BASE, SWOOLE_SOCK_TCP);
        $profilingEnabled = (bool) $input->getOption('enable-profiling');
        $staticFilesServingEnabled = (bool) $input->getOption('enable-static');
        $driver = new ConsoleDebugDriver($this->kernel, $output, $profilingEnabled);
        $requestLimit = (int) $input->getArgument('requests');

        if ($staticFilesServingEnabled) {
            $server->set([
                'enable_static_handler' => true,
                'document_root' => $this->kernel->getRootDir().'/public',
            ]);
        }

        $server->on('request', function (Request $request, Response $response) use ($driver, $requestLimit, $server, $output) {
            $driver->handle($request, $response);

            $this->requestCounter->increment();
            $current = $this->requestCounter->get();

            if (1 === $current) {
                $output->writeln('<comment>First response has been sent</comment>');
            }

            if ($requestLimit === $current) {
                $output->writeln('<comment>Request limit has been hit</comment>');
                $server->stop();
            }
        });

        $trustedHosts = ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_HOSTS']);
        $trustedProxies = ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_PROXIES']);
        $driver->boot($trustedHosts, $trustedProxies);
        $output->writeln(\sprintf('<info>Swoole HTTP Server started on http://%s:%d for %d requests</info>', $host, $port, $requestLimit));

        $rows = [
            ['env', $this->kernel->getEnvironment()],
            ['debug', \var_export($this->kernel->isDebug(), true)],
            ['profiling', \var_export($profilingEnabled, true)],
            ['memory_limit', ServerUtils::formatBytes(ServerUtils::getMaxMemory())],
            ['trusted_hosts', \implode(', ', $trustedHosts)],
            ['trusted_proxies', \implode(', ', $trustedProxies)],
        ];

        if ($staticFilesServingEnabled) {
            $rows[] = ['document_root', $this->kernel->getRootDir().'/public'];
        }

        $io->newLine();
        $io->table(['Configuration', 'Values'], $rows);

        $server->start();
    }
}
