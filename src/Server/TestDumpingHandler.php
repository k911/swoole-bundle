<?php

declare(strict_types=1);

namespace K911\Swoole\Server;

final class TestDumpingHandler
{
    private $text;

    public function __construct(string $text = 'default')
    {
        $this->text = $text;
    }

    public function setText(string $text): void
    {
        $this->text = $text;
    }

    public function handle(): void
    {
        echo $this->text.\PHP_EOL;
    }
}
