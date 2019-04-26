<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Functions;

use K911\Swoole\Common\Reflection;
use PHPUnit\Framework\TestCase;

class ReplaceObjectPropertyTest extends TestCase
{
    /**
     * @var TestObject
     */
    private $testObject;

    protected function setUp(): void
    {
        $this->testObject = new TestObject();
    }

    public function testReplacePublicProperty(): void
    {
        $this->assertSame(TestObject::WRONG_VALUE, $this->testObject->getPublicProp());

        Reflection::replaceObjectProperty($this->testObject, 'publicProp', TestObject::GOOD_VALUE);

        $this->assertSame(TestObject::GOOD_VALUE, $this->testObject->getPublicProp());
    }

    public function testReplaceProtectedProperty(): void
    {
        $this->assertSame(TestObject::WRONG_VALUE, $this->testObject->getPublicProp());

        Reflection::replaceObjectProperty($this->testObject, 'protectedProp', TestObject::GOOD_VALUE);

        $this->assertSame(TestObject::GOOD_VALUE, $this->testObject->getProtectedProp());
    }

    public function testReplacePrivateProperty(): void
    {
        $this->assertSame(TestObject::WRONG_VALUE, $this->testObject->getPublicProp());

        Reflection::replaceObjectProperty($this->testObject, 'privateProp', TestObject::GOOD_VALUE);

        $this->assertSame(TestObject::GOOD_VALUE, $this->testObject->getPrivateProp());
    }

    public function testReplaceDynamicProperty(): void
    {
        $this->assertSame(TestObject::WRONG_VALUE, $this->testObject->getPublicProp());

        Reflection::replaceObjectProperty($this->testObject, 'dynamicProp', TestObject::GOOD_VALUE);

        $this->assertSame(TestObject::GOOD_VALUE, $this->testObject->getDynamicProp());
    }
}
