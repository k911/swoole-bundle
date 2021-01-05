<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Functions;

use function K911\Swoole\replace_object_property;
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
        self::assertSame(TestObject::WRONG_VALUE, $this->testObject->getPublicProp());

        replace_object_property($this->testObject, 'publicProp', TestObject::GOOD_VALUE);

        self::assertSame(TestObject::GOOD_VALUE, $this->testObject->getPublicProp());
    }

    public function testReplaceProtectedProperty(): void
    {
        self::assertSame(TestObject::WRONG_VALUE, $this->testObject->getPublicProp());

        replace_object_property($this->testObject, 'protectedProp', TestObject::GOOD_VALUE);

        self::assertSame(TestObject::GOOD_VALUE, $this->testObject->getProtectedProp());
    }

    public function testReplacePrivateProperty(): void
    {
        self::assertSame(TestObject::WRONG_VALUE, $this->testObject->getPublicProp());

        replace_object_property($this->testObject, 'privateProp', TestObject::GOOD_VALUE);

        self::assertSame(TestObject::GOOD_VALUE, $this->testObject->getPrivateProp());
    }

    public function testReplaceDynamicProperty(): void
    {
        self::assertSame(TestObject::WRONG_VALUE, $this->testObject->getPublicProp());

        replace_object_property($this->testObject, 'dynamicProp', TestObject::GOOD_VALUE);

        self::assertSame(TestObject::GOOD_VALUE, $this->testObject->getDynamicProp());
    }
}
