<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\MessageHandler;

use Assert\Assertion;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Message\CreateFileMessage;
use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Test\ServerTestCase;

final class CreateFileMessageHandler
{
    public function __invoke(CreateFileMessage $message): void
    {
        $filePath = ServerTestCase::FIXTURE_RESOURCES_DIR.\DIRECTORY_SEPARATOR.\ltrim($message->fileName(), '\\/');
        $result = \file_put_contents($filePath, $message->content());
        Assertion::true(false !== $result, 'Could not create test file.');
    }
}
