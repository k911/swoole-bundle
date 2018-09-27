<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends Controller
{
    /**
     * @Route(
     *     methods={"GET"},
     *     path="/"
     * )
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return new JsonResponse(['hello' => 'world!'], 200);
    }
}
