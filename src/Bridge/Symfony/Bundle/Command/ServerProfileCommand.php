<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Bundle\Command;

use Assert\Assertion;
use K911\Swoole\Server\HttpServerConfiguration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

final class ServerProfileCommand extends AbstractServerStartCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('swoole:server:profile')
            ->setDescription('Handles specified amount of requests to a local swoole server. Useful for profiling.')
            ->addArgument('requests', InputArgument::REQUIRED, 'Number of requests to handle by the server');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Assert\AssertionFailedException
     */
    protected function prepareRuntimeConfiguration(HttpServerConfiguration $serverConfiguration, InputInterface $input): array
    {
        $requestLimit = $input->getArgument('requests');
        Assertion::numeric($requestLimit);
        Assertion::greaterOrEqualThan($requestLimit, 0, 'Request limit must be greater than 0');

        return ['requestLimit' => $requestLimit] + parent::prepareRuntimeConfiguration($serverConfiguration, $input);
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareConfigurationRowsToPrint(HttpServerConfiguration $serverConfiguration, array $runtimeConfiguration): array
    {
        $rows = parent::prepareConfigurationRowsToPrint($serverConfiguration, $runtimeConfiguration);
        $rows[] = ['request_limit', $runtimeConfiguration['requestLimit'] > 0 ? $runtimeConfiguration['requestLimit'] : -1];

        return $rows;
    }
}
