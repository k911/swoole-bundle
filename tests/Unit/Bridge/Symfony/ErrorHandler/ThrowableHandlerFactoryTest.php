<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Bridge\Symfony\ErrorHandler;

use K911\Swoole\Bridge\Symfony\ErrorHandler\ThrowableHandlerFactory;
use PHPUnit\Framework\TestCase;

class ThrowableHandlerFactoryTest extends TestCase
{
    public function testThrowableHandlerCreation(): void
    {
        $handler = ThrowableHandlerFactory::newThrowableHandler();
        $methodName = $handler->getName();

        self::assertTrue('handleThrowable' === $methodName || 'handleException' === $methodName);
    }
}
