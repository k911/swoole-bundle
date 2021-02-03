<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\Command;

use Assert\Assertion;
use K911\Swoole\Client\Exception\ClientConnectionErrorException;
use K911\Swoole\Coroutine\CoroutinePool;
use K911\Swoole\Server\Api\ApiServerClientFactory;
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
    private $apiServerClientFactory;
    private $sockets;
    private $parameterBag;

    public function __construct(
        Sockets $sockets,
        ApiServerClientFactory $apiServerClientFactory,
        ParameterBagInterface $parameterBag
    ) {
        $this->sockets = $sockets;
        $this->apiServerClientFactory = $apiServerClientFactory;
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
            ->addOption('api-port', null, InputOption::VALUE_REQUIRED, 'API Server listens on this port.', $this->parameterBag->get('swoole.http_server.api.port'))
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Assert\AssertionFailedException
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exitCode = 0;
        $io = new SymfonyStyle($input, $output);

        $this->prepareClientConfiguration($input);

        $coroutinePool = CoroutinePool::fromCoroutines(function () use ($io): void {
            $status = $this->apiServerClientFactory->newClient()
                ->status()
            ;
            $io->success('Fetched status');
            $this->showStatus($io, $status);
        }, function () use ($io): void {
            $metrics = $this->apiServerClientFactory->newClient()
                ->metrics()
            ;
            $io->success('Fetched metrics');
            $this->showMetrics($io, $metrics);
        });

        try {
            $coroutinePool->run();
        } catch (ClientConnectionErrorException $errorException) {
            $io->error('An error occurred while connecting to the API Server. Please verify configuration.');
            $exitCode = 1;
        }

        return $exitCode;
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    protected function prepareClientConfiguration(InputInterface $input): void
    {
        /** @var string $host */
        $host = $input->getOption('api-host');

        /** @var string $port */
        $port = $input->getOption('api-port');

        Assertion::numeric($port, 'Port must be a number.');
        Assertion::string($host, 'Host must be a string.');

        $this->sockets->changeApiSocket(new Socket($host, (int) $port));
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
        $date = \DateTimeImmutable::createFromFormat(\DATE_ATOM, $metrics['date']);
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
            ['All workers', $workers, '1'],
            ['Active workers', $activeWorkers, '1'],
            ['Idle workers', $idleWorkers, '1'],
            ['Running coroutines', $server['coroutine_num'], '1'],
            ['Tasks in queue', $server['tasking_num'], '1'],
        ]);
    }
}
