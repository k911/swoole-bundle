<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server;

use Exception;

final class IdMother
{
    public static function random(): int
    {
        try {
            return \random_int(0, 10000);
        } catch (Exception $ex) {
            return 0;
        }
    }
}
