<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Bridge\Symfony\HttpFoundation;

use K911\Swoole\Bridge\Symfony\HttpFoundation\SetRequestRuntimeConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class SetRequestRuntimeConfigurationTest extends TestCase
{
    /**
     * @var SetRequestRuntimeConfiguration
     */
    private $configuration;

    protected function setUp(): void
    {
        $this->configuration = new SetRequestRuntimeConfiguration();
    }

    public function testBoot(): void
    {
        $configuration = [
            'trustedHosts' => ['127.0.0.1', 'localhost'],
            'trustedProxies' => ['192.168.1.0/24', '73.41.22.1', 'varnish'],
            'trustedHeaderSet' => Request::HEADER_X_FORWARDED_AWS_ELB,
        ];

        $this->configuration->boot($configuration);

        $this->assertSame(['{127.0.0.1}i', '{localhost}i'], Request::getTrustedHosts());

        $this->assertSame($configuration['trustedProxies'], Request::getTrustedProxies());
        $this->assertSame($configuration['trustedHeaderSet'], Request::getTrustedHeaderSet());
    }
}
