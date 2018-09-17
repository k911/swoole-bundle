<?php

declare(strict_types=1);

namespace App\Bundle\SwooleBundle\Bridge\Symfony\HttpFoundation;

use App\Bundle\SwooleBundle\Server\Runtime\BootableInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Sets symfony's request runtime configuration.
 */
final class SetRequestRuntimeConfiguration implements BootableInterface
{
    /**
     * {@inheritdoc}
     */
    public function boot(array $runtimeConfiguration = []): void
    {
        if (\array_key_exists('trustedHosts', $runtimeConfiguration)) {
            Request::setTrustedHosts($runtimeConfiguration['trustedHosts']);
        }
        if (\array_key_exists('trustedProxies', $runtimeConfiguration)) {
            Request::setTrustedProxies($runtimeConfiguration['trustedProxies'], $runtimeConfiguration['trustedHeaderSet'] ?? Request::HEADER_X_FORWARDED_ALL);
        }
    }
}
