<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Controller;

use K911\Swoole\Tests\Fixtures\Symfony\TestBundle\Service\DummyService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DoctrineController
{
    /**
     * @var DummyService
     */
    private $dummyService;

    public function __construct(DummyService $dummyService)
    {
        $this->dummyService = $dummyService;
    }

    /**
     * @Route(
     *     methods={"GET"},
     *     path="/doctrine"
     * )
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function index()
    {
        $tests = $this->dummyService->process();

        $testsStr = '';

        foreach ($tests as $test) {
            $testsStr .= $test->getUuid().'<br>';
        }

        return new Response(
            '<html><body>'.$testsStr.'</body></html>'
        );
    }
}
