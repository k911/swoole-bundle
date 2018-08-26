<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Command;

use App\Bundle\SwooleBundle\Driver\HttpDriverInterface;
use App\Bundle\SwooleBundle\Server\ServerUtils;
use Assert\Assertion;
use Composer\XdebugHandler\XdebugHandler;
use Swoole\Http\Server;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

final class ServerRunCommand extends Command
{
    private $kernel;
    private $server;
    private $driver;

    /**
     * @param KernelInterface     $kernel
     * @param Server              $server
     * @param HttpDriverInterface $driver
     */
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
        $this->setName('swoole:server:run')
            ->setDescription('Runs a local swoole server')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Host of the server')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Port of the server')
            ->addOption('enable-static', null, InputOption::VALUE_NONE, 'Enables static files serving');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     * @throws \InvalidArgumentException
     * @throws \Exception
     * @throws \Assert\AssertionFailedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $xdebug = new XdebugHandler('swoole');
        $xdebug->check();
        unset($xdebug);

        $io = new SymfonyStyle($input, $output);

        $host = (string) ($input->getOption('host') ?? $this->server->host);
        $port = (int) ($input->getOption('port') ?? $this->server->port);
        $this->server->host = $host;
        $this->server->port = $port;

        $staticFilesServingEnabled = (bool) $input->getOption('enable-static');

        $cpuCount = \swoole_cpu_num();
        $workerCount = $cpuCount * 2;
        $settings = [
            'reactor_num' => $cpuCount,
            'worker_num' => $workerCount,
        ];

        if ($staticFilesServingEnabled) {
            $publicDir = \dirname($this->kernel->getRootDir()).'/public';
            Assertion::directory($publicDir, 'Public directory does not exists. Tried "%s".');

            $settings['enable_static_handler'] = true;
            $settings['document_root'] = $publicDir;
        }

        $this->server->on('request', [$this->driver, 'handle']);

        $this->driver->boot([
            'trustedHosts' => ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_HOSTS']),
            'trustedProxies' => ServerUtils::decodeStringAsSet($_SERVER['APP_TRUSTED_PROXIES']),
        ]);

        $rows = [
            ['env', $this->kernel->getEnvironment()],
            ['debug', \var_export($this->kernel->isDebug(), true)],
            ['worker_count', $workerCount],
            ['memory_limit', ServerUtils::formatBytes(ServerUtils::getMaxMemory())],
        ];

        if (isset($publicDir)) {
            $rows[] = ['document_root', $publicDir];
        }

        $output->writeln(\sprintf('<info>Swoole HTTP Server started on http://%s:%d</info>', $host, $port));
        $io->newLine();
        $io->table(['Configuration', 'Values'], $rows);

        $this->server->set($settings);
        $this->server->start();
    }
}
