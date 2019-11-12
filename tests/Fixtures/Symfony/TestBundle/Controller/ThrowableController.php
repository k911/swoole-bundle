<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;

final class ThrowableController
{
    /**
     * @Route(
     *     methods={"GET"},
     *     path="/throwable/error"
     * )
     */
    public function error(): void
    {
        try {
            throw new \Exception('Previous', 5001);
        } catch (\Throwable $exception) {
            throw new \Error('Critical failure', 5000, $exception);
        }
    }

    /**
     * @Route(
     *     methods={"GET"},
     *     path="/throwable/exception"
     * )
     */
    public function exception(): void
    {
        throw new \RuntimeException('An exception has occurred', 5000);
    }
}
