<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Functions;

use K911\Swoole\Common\Reflection;
use PHPUnit\Framework\TestCase;

class GetObjectPropertyTest extends TestCase
{
    /**
     * @var TestObject
     */
    private $testObject;

    protected function setUp(): void
    {
        $this->testObject = new TestObject(TestObject::GOOD_VALUE);
    }

    public function testGetPublicProperty(): void
    {
        $this->assertSame(TestObject::GOOD_VALUE, Reflection::getObjectProperty($this->testObject, 'publicProp'));
    }

    public function testGetProtectedProperty(): void
    {
        $this->assertSame(TestObject::GOOD_VALUE, Reflection::getObjectProperty($this->testObject, 'protectedProp'));
    }

    public function testGetPrivateProperty(): void
    {
        $this->assertSame(TestObject::GOOD_VALUE, Reflection::getObjectProperty($this->testObject, 'privateProp'));
    }

    public function testGetDynamicProperty(): void
    {
        $this->assertSame(TestObject::GOOD_VALUE, Reflection::getObjectProperty($this->testObject, 'dynamicProp'));
    }
}
