<?php

declare(strict_types=1);

namespace App\Tests\Bundle\SwooleBundle\Functions;

use PHPUnit\Framework\TestCase;
use function App\Bundle\SwooleBundle\Functions\replace_object_property;

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

        replace_object_property($this->testObject, 'publicProp', TestObject::GOOD_VALUE);

        $this->assertSame(TestObject::GOOD_VALUE, $this->testObject->getPublicProp());
    }

    public function testReplaceProtectedProperty(): void
    {
        $this->assertSame(TestObject::WRONG_VALUE, $this->testObject->getPublicProp());

        replace_object_property($this->testObject, 'protectedProp', TestObject::GOOD_VALUE);

        $this->assertSame(TestObject::GOOD_VALUE, $this->testObject->getProtectedProp());
    }

    public function testReplacePrivateProperty(): void
    {
        $this->assertSame(TestObject::WRONG_VALUE, $this->testObject->getPublicProp());

        replace_object_property($this->testObject, 'privateProp', TestObject::GOOD_VALUE);

        $this->assertSame(TestObject::GOOD_VALUE, $this->testObject->getPrivateProp());
    }

    public function testReplaceDynamicProperty(): void
    {
        $this->assertSame(TestObject::WRONG_VALUE, $this->testObject->getPublicProp());

        replace_object_property($this->testObject, 'dynamicProp', TestObject::GOOD_VALUE);

        $this->assertSame(TestObject::GOOD_VALUE, $this->testObject->getDynamicProp());
    }
}
