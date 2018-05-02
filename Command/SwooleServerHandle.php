<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Command;

use App\Bundle\SwooleBundle\Server\Counter;
use App\Bundle\SwooleBundle\Server\Driver;
use App\Bundle\SwooleBundle\Server\ServerUtils;
use App\Kernel;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
            ->addOption('log-output', null, InputOption::VALUE_REQUIRED, 'Log output location', 'php://stdout')
            ->addOption('enable-profiling', null, InputOption::VALUE_NONE, 'Enables server profiling');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \OutOfRangeException
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $logger = $this->makeLogger($input->getOption('log-output'));
        $host = $input->getOption('host');
        $port = (int) $input->getOption('port');
        $server = $this->makeServer($host, $port);
        $driver = $this->makeDriver($this->kernel, $logger);
        $profilingEnabled = (bool) $input->getOption('enable-profiling');

        if ($profilingEnabled) {
            $driver->enableProfiling();
        }

        $requestLimit = (int) $input->getArgument('requests');
        $server->on('request', function (Request $request, Response $response) use ($driver, $requestLimit, $server, $logger, $profilingEnabled) {
            $driver->handle($request, $response);

            $this->requestCounter->increment();
            $current = $this->requestCounter->get();

            if (1 === $current && $profilingEnabled) {
                $logger->info('First response has been sent', [
                    'memory_usage' => ServerUtils::formatBytes(ServerUtils::getMemoryUsage()),
                    'memory_peak_usage' => ServerUtils::formatBytes(ServerUtils::getPeakMemoryUsage()),
                ]);
            }

            if ($requestLimit === $current) {
                if ($profilingEnabled) {
                    $logger->info('Request limit has been hit. Closing connections..', [
                        'memory_usage' => ServerUtils::formatBytes(ServerUtils::getMemoryUsage()),
                        'memory_peak_usage' => ServerUtils::formatBytes(ServerUtils::getPeakMemoryUsage()),
                    ]);
                }
                $server->stop();
            }
        });

        $trustedHosts = ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_HOSTS']);
        $trustedProxies = ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_PROXIES']);

        $logger->info(\sprintf('Swoole HTTP Server started on http://%s:%d', $host, $port), [
            'env' => $this->kernel->getEnvironment(),
            'debug' => $this->kernel->isDebug() ? 'true' : 'false',
            'profiling' => $profilingEnabled ? 'true' : 'false',
            'memory_limit' => ServerUtils::formatBytes(ServerUtils::getMaxMemory()),
            'memory_usage' => ServerUtils::formatBytes(ServerUtils::getMemoryUsage()),
            'memory_peak_usage' => ServerUtils::formatBytes(ServerUtils::getPeakMemoryUsage()),
            'trusted_hosts' => $trustedHosts,
            'trusted_proxies' => $trustedProxies,
            'request_limit' => $requestLimit,
        ]);

        $driver->boot($trustedHosts, $trustedProxies);
        $server->start();
    }

    /**
     * @param string $output
     *
     * @throws \Exception
     *
     * @return LoggerInterface
     */
    private function makeLogger(string $output): LoggerInterface
    {
        return new Logger('swoole', [new StreamHandler($output)]);
    }

    /**
     * @param string $host
     * @param int    $port
     *
     * @return Server
     */
    private function makeServer(string $host, int $port): Server
    {
        return new Server($host, $port, SWOOLE_BASE);
    }

    /**
     * @param Kernel          $kernel
     * @param LoggerInterface $logger
     *
     * @return Driver
     */
    private function makeDriver(Kernel $kernel, LoggerInterface $logger): Driver
    {
        return new Driver($kernel, $logger);
    }
}
