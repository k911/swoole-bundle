<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Message;

final class CreateFileMessage
{
    private $fileName;
    private $content;

    public function __construct(string $fileName, string $content)
    {
        $this->fileName = $fileName;
        $this->content = $content;
    }

    public function fileName(): string
    {
        return $this->fileName;
    }

    public function content(): string
    {
        return $this->content;
    }
}
