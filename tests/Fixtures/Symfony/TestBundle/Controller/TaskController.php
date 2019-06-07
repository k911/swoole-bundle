<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Controller;

use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Message\CreateFileMessage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

final class TaskController
{
    /**
     * @Route(
     *     methods={"GET","POST"},
     *     path="/message/dispatch"
     * )
     *
     * @param MessageBusInterface $bus
     * @param Request             $request
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function dispatchMessage(MessageBusInterface $bus, Request $request): Response
    {
        $fileName = $request->get('fileName', 'test-default-file.txt');
        $content = $request->get('content', (new \DateTimeImmutable())->format(\DATE_ATOM));
        $message = new CreateFileMessage($fileName, $content);
        $bus->dispatch($message);

        return new Response('OK', 200);
    }
}
