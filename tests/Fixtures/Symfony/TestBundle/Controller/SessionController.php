<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class SessionController
{
    /**
     * @Route(methods={"GET"}, path="/session")
     * @Route(methods={"GET"}, path="/session/1")
     * @Route(methods={"GET"}, path="/session/2")
     *
     * @throws \Exception
     */
    public function index(SessionInterface $session): JsonResponse
    {
        if (!$session->has('luckyNumber')) {
            $session->set('luckyNumber', \random_int(1, 1000000));
        }

        $metadata = $session->getMetadataBag();

        return new JsonResponse([
            'hello' => 'world!',
            'sessionMetadata' => [
                'created_at' => $metadata->getCreated(),
                'updated_at' => $metadata->getLastUsed(),
                'lifetime' => $metadata->getLifetime(),
            ],
            'luckyNumber' => $session->get('luckyNumber'),
        ], 200);
    }
}
