<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Command;

final class ServerRunCommand extends AbstractServerStartCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('swoole:server:run')
            ->setDescription('Runs a local swoole http server');

        parent::configure();
    }
}
