<?php

declare(strict_types=1);

namespace K911\Swoole\Server\RequestHandler;

use InvalidArgumentException;
use K911\Swoole\Component\AtomicCounter;
use K911\Swoole\Server\HttpServer;
use K911\Swoole\Server\Runtime\BootableInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Symfony\Component\Console\Style\SymfonyStyle;

final class LimitedRequestHandler implements RequestHandlerInterface, BootableInterface
{
    private $requestLimit;
    private $server;
    private $requestCounter;
    private $decorated;
    private $symfonyStyle;

    public function __construct(RequestHandlerInterface $decorated, HttpServer $server, AtomicCounter $counter)
    {
        $this->decorated = $decorated;
        $this->server = $server;
        $this->requestCounter = $counter;
    }

    /**
     * {@inheritdoc}
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        $this->requestLimit = (int) ($runtimeConfiguration['requestLimit'] ?? -1);
        $this->symfonyStyle = $runtimeConfiguration['symfonyStyle'] ?? null;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Assert\AssertionFailedException
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

                $this->server->shutdown();
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
