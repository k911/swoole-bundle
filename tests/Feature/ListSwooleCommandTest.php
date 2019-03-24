<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Feature;

use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;

final class ListSwooleCommandTest extends ServerTestCase
{
    public function testRunAndCall(): void
    {
        $server = $this->createConsoleProcess(['list', 'swoole']);

        $server->disableOutput();
        $server->setTimeout(3);
        $server->run();

        $this->assertTrue($server->isSuccessful());
    }
}
