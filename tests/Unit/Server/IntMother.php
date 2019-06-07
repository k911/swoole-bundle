<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server;

use Exception;

final class IntMother
{
    public static function random(): int
    {
        try {
            return \random_int(0, 10000);
        } catch (Exception $ex) {
            return 0;
        }
    }

    public static function randomPositive(): int
    {
        try {
            return \random_int(1, 10000);
        } catch (Exception $ex) {
            return 0;
        }
    }
}
