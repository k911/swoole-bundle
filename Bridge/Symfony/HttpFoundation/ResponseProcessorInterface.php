<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation;

use Swoole\Http\Response as SwooleResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

interface ResponseProcessorInterface
{
    /**
     * @param \Symfony\Component\HttpFoundation\Response $httpFoundationResponse
     * @param \Swoole\Http\Response                      $swooleSwooleResponse
     */
    public function process(HttpFoundationResponse $httpFoundationResponse, SwooleResponse $swooleSwooleResponse): void;
}
