<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\Command;

use Assert\Assertion;
use K911\Swoole\Server\Api\ApiServerInterface;
use K911\Swoole\Server\Config\Socket;
use K911\Swoole\Server\Config\Sockets;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class ServerStatusCommand extends Command
{
    private $apiServer;
    private $sockets;
    private $parameterBag;
    private $testing = false;

    public function __construct(
        Sockets $sockets,
        ApiServerInterface $apiServer,
        ParameterBagInterface $parameterBag
    ) {
        $this->apiServer = $apiServer;
        $this->sockets = $sockets;
        $this->parameterBag = $parameterBag;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Get current status of the Swoole HTTP Server by querying running API Server.')
            ->addOption('api-host', null, InputOption::VALUE_REQUIRED, 'API Server listens on this host.', $this->parameterBag->get('swoole.http_server.api.host'))
            ->addOption('api-port', null, InputOption::VALUE_REQUIRED, 'API Server listens on this port.', $this->parameterBag->get('swoole.http_server.api.port'));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Assert\AssertionFailedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->prepareClientConfiguration($input);

        $this->goAndWait(function () use ($io): void {
            try {
                $status = $this->apiServer->status();
                $metrics = $this->apiServer->metrics();
            } catch (\RuntimeException $runtimeException) {
                $io->error('Could not connect to Swoole API Server');

                return;
            }
            $io->success('Fetched status and metrics');
            $this->showStatus($io, $status);
            $this->showMetrics($io, $metrics);
        });

        return 0;
    }

    public function goAndWait(callable $callback): void
    {
        if ($this->testing) {
            $callback();

            return;
        }

        \go($callback);
        \swoole_event_wait();
    }

    private function showStatus(SymfonyStyle $io, array $status): void
    {
        $server = $status['server'];
        $processes = $server['processes'];

        $rows = [
            ['Host', $server['host']],
            ['Port', $server['port']],
            ['Running mode', $server['runningMode']],
            ['Master PID', $processes['master']['pid']],
            ['Manager PID', $processes['manager']['pid']],
            [\sprintf('Worker[%d] PID', $processes['worker']['id']), $processes['worker']['pid']],
        ];

        foreach ($server['listeners'] as $id => ['host' => $host, 'port' => $port]) {
            $rows[] = [\sprintf('Listener[%d] Host', $id), $host];
            $rows[] = [\sprintf('Listener[%d] Port', $id), $port];
        }

        $io->table([
            'Configuration', 'Value',
        ], $rows);
    }

    private function showMetrics(SymfonyStyle $io, array $metrics): void
    {
        $date = \DateTimeImmutable::createFromFormat(DATE_ATOM, $metrics['date']);
        Assertion::isInstanceOf($date, \DateTimeImmutable::class);
        $server = $metrics['server'];
        $runningSeconds = $date->getTimestamp() - $server['start_time'];

        $idleWorkers = $server['idle_worker_num'];
        $workers = $server['worker_num'];
        $activeWorkers = $workers - $idleWorkers;

        $io->table([
            'Metric', 'Quantity', 'Unit',
        ], [
            ['Requests', $server['request_count'], '1'],
            ['Up time', $runningSeconds, 'Seconds'],
            ['Active connections', $server['connection_num'], '1'],
            ['Accepted connections', $server['accept_count'], '1'],
            ['Closed connections', $server['close_count'], '1'],
            ['Active workers', $activeWorkers, '1'],
            ['Idle workers', $idleWorkers, '1'],
        ]);
    }

    /**
     * @param InputInterface $input
     *
     * @throws \Assert\AssertionFailedException
     */
    protected function prepareClientConfiguration(InputInterface $input): void
    {
        $host = $input->getOption('api-host');
        $port = $input->getOption('api-port');

        Assertion::numeric($port, 'Port must be a number.');
        Assertion::string($host, 'Host must be a string.');

        $this->sockets->changeApiSocket(new Socket($host, (int) $port));
    }

    public function enableTestMode(): void
    {
        $this->testing = true;
    }
}
