<?php

declare(strict_types=1);

namespace App\Tests\Bundle\SwooleBundle\Functions;

use PHPUnit\Framework\TestCase;
use function App\Bundle\SwooleBundle\Functions\get_object_property;

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
        $this->assertSame(TestObject::GOOD_VALUE, get_object_property($this->testObject, 'publicProp'));
    }

    public function testGetProtectedProperty(): void
    {
        $this->assertSame(TestObject::GOOD_VALUE, get_object_property($this->testObject, 'protectedProp'));
    }

    public function testGetPrivateProperty(): void
    {
        $this->assertSame(TestObject::GOOD_VALUE, get_object_property($this->testObject, 'privateProp'));
    }

    public function testGetDynamicProperty(): void
    {
        $this->assertSame(TestObject::GOOD_VALUE, get_object_property($this->testObject, 'dynamicProp'));
    }
}
