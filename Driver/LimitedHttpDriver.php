<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Driver;

use App\Bundle\SwooleBundle\Server\AtomicCounter;
use InvalidArgumentException;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Symfony\Component\Console\Style\SymfonyStyle;

final class LimitedHttpDriver implements HttpDriverInterface
{
    private $requestLimit;
    private $server;
    private $requestCounter;
    private $decorated;
    private $symfonyStyle;

    public function __construct(HttpDriverInterface $decorated, Server $server, AtomicCounter $counter)
    {
        $this->decorated = $decorated;
        $this->server = $server;
        $this->requestCounter = $counter;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(array $configuration = []): void
    {
        $this->requestLimit = (int) ($configuration['requestLimit'] ?? -1);
        $this->symfonyStyle = $configuration['symfonyStyle'] ?? null;

        $this->decorated->boot($configuration);
    }

    /**
     * Handles swoole request and modifies swoole response accordingly.
     *
     * @param \Swoole\Http\Request  $request
     * @param \Swoole\Http\Response $response
     */
    public function handle(Request $request, Response $response): void
    {
        $this->decorated->handle($request, $response);

        if ($this->requestLimit > 0) {
            $this->requestCounter->increment();

            $requestNo = $this->requestCounter->get();
            if (1 === $requestNo) {
                $this->console(function (SymfonyStyle $io): void {
                    $io->success('First response has been sent!');
                });
            }

            if ($this->requestLimit === $this->requestCounter->get()) {
                $this->console(function (SymfonyStyle $io): void {
                    $io->caution([
                        'Request limit has been hit!',
                        'Stopping server..',
                    ]);
                });

                $this->server->stop();
            }
        }
    }

    private function console(callable $callback)
    {
        if (!$this->symfonyStyle instanceof SymfonyStyle) {
            throw new InvalidArgumentException('To interact with console, SymfonyStyle object must be provided as "symfonyStyle" attribute.');
        }

        return $callback($this->symfonyStyle);
    }
}
