<?php

declare(strict_types=1);

namespace K911\Swoole\Bridge\Symfony\Container\Proxy\Generation\PropertyGenerator;

use K911\Swoole\Bridge\Symfony\Container\ServicePool;
use Laminas\Code\Generator\DocBlockGenerator;
use Laminas\Code\Generator\Exception\InvalidArgumentException;
use Laminas\Code\Generator\PropertyGenerator;
use ProxyManager\Generator\Util\IdentifierSuffixer;
use ReflectionClass;

/**
 * Property that contains the wrapped Symfony container.
 */
class ServicePoolHolderProperty extends PropertyGenerator
{
    private static ?ReflectionClass $servicePoolReflection = null;

    /**
     * Constructor.
     *
     * @throws InvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct(IdentifierSuffixer::getIdentifier('servicePool'));

        $docBlock = new DocBlockGenerator();

        $docBlock->setWordWrap(false);
        $docBlock->setLongDescription('@var \\'.self::getServicePoolReflection()->getName().' ServicePool holder');
        $this->setDocBlock($docBlock);
        $this->setVisibility(self::VISIBILITY_PRIVATE);
    }

    private static function getServicePoolReflection(): ReflectionClass
    {
        if (null === self::$servicePoolReflection) {
            self::$servicePoolReflection = new ReflectionClass(ServicePool::class);
        }

        return self::$servicePoolReflection;
    }
}
