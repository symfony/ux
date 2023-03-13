<?php

namespace Symfony\UX\LiveComponent\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\Util\DehydratedProps;

class DehydratedPropsTest extends TestCase
{
    public function testDataIsBuiltCorrectly(): void
    {
        $dehydratedProps = new DehydratedProps();
        $dehydratedProps->addPropValue('firstName', 'Ryan');
        $dehydratedProps->addPropValue('address', ['street' => '123 Main St', 'city' => 'New York']);
        $dehydratedProps->addPropValue('product', 5);

        $dehydratedProps->addNestedProp('address', 'city', 'New York');
        $dehydratedProps->addNestedProp('product', 'name', 'marshmallows');
        $dehydratedProps->addNestedProp('product', 'price', '25.99');
        $dehydratedProps->addNestedProp('product', 'category.title', 'campfire food');

        $this->assertSame([
            'firstName' => 'Ryan',
            'address' => ['street' => '123 Main St', 'city' => 'New York'],
            'product' => 5,
        ], $dehydratedProps->getProps());
        $this->assertSame([
            'address.city' => 'New York',
            'product.name' => 'marshmallows',
            'product.price' => '25.99',
            'product.category.title' => 'campfire food',
        ], $dehydratedProps->getNestedProps());

        // now reverse the process
        $propsFromArray = DehydratedProps::createFromPropsArray($dehydratedProps->getProps());
        $this->assertEquals($dehydratedProps->getProps(), $propsFromArray->getProps());

        $updatedProps = DehydratedProps::createFromUpdatedArray($dehydratedProps->getNestedProps());
        $this->assertEquals($dehydratedProps->getNestedProps(), $updatedProps->getNestedProps());
    }

    public function testRemovePropValue(): void
    {
        $props = new DehydratedProps();
        $props->addPropValue('firstName', 'Ryan');
        $props->addPropValue('lastName', 'Weaver');
        $props->removePropValue('firstName');
        $this->assertSame(['lastName' => 'Weaver'], $props->getProps());
    }

    public function testGetAndHasPropValue(): void
    {
        $props = new DehydratedProps();
        $props->addPropValue('firstName', 'Ryan');
        $props->addPropValue('lastName', 'Weaver');
        $this->assertTrue($props->hasPropValue('firstName'));
        $this->assertFalse($props->hasPropValue('middleName'));
        $this->assertSame('Ryan', $props->getPropValue('firstName'));
        $this->assertNull($props->getPropValue('middleName'));
    }

    public function testGetAndHasNestedPathValue()
    {
        $props = new DehydratedProps();
        $props->addPropValue('student', '11');
        $props->addNestedProp('student', 'firstName', 'Ryan');
        $props->addNestedProp('student', 'lastName', 'Weaver');

        $this->assertTrue($props->hasNestedPathValue('student', 'firstName'));
        $this->assertFalse($props->hasNestedPathValue('student', 'middleName'));
        $this->assertSame('Ryan', $props->getNestedPathValue('student', 'firstName'));

        $props->addPropValue('product', '9');
        $props->addNestedProp('product', 'category.name', 'Campfire Food');
        $this->assertTrue($props->hasNestedPathValue('product', 'category.name'));
        $this->assertSame('Campfire Food', $props->getNestedPathValue('product', 'category.name'));
    }

    public function testCreateFromUpdatedArray(): void
    {
        $actual = DehydratedProps::createFromUpdatedArray([
            'isPublic' => true,
            'student' => 10,
            'student.firstName' => 'Ryan',
            'student.lastName' => 'Weaver',
            'product.name' => 'Smores',
            'product.category.name' => 'Campfire Food',
        ]);

        $expected = new DehydratedProps();
        $expected->addPropValue('isPublic', true);
        $expected->addPropValue('student', 10);
        $expected->addNestedProp('student', 'firstName', 'Ryan');
        $expected->addNestedProp('student', 'lastName', 'Weaver');
        $expected->addNestedProp('product', 'name', 'Smores');
        $expected->addNestedProp('product', 'category.name', 'Campfire Food');

        $this->assertEquals($expected, $actual);
    }

    public function testGetNestedPathsForProperty(): void
    {
        $props = DehydratedProps::createFromUpdatedArray([
            'invoice.number' => '123',
            'invoice.lineItems.0.quantity' => 1,
            'invoice.lineItems.1.price' => 300,
            'invoice.linesItems.2' => ['quantity' => 5, 'price' => 100],
        ]);

        $actual = $props->getNestedPathsForProperty('invoice');
        $this->assertSame(
            ['number', 'lineItems.0.quantity', 'lineItems.1.price', 'linesItems.2'],
            $actual
        );
    }

    public function testCalculateUnexpectedWritablePaths(): void
    {
        $props = DehydratedProps::createFromUpdatedArray([
            'product.tags' => ['pretzels', 'nonsense'],
            'product.name' => 'bananas',
        ]);

        $this->assertEmpty($props->calculateUnexpectedNestedPathsForProperty(
            'product',
            ['tags', 'name']
        ));
        $this->assertEmpty($props->calculateUnexpectedNestedPathsForProperty(
            'product',
            ['tags', 'name', 'other']
        ));
        $this->assertSame(['name'], $props->calculateUnexpectedNestedPathsForProperty(
            'product',
            ['tags']
        ));
        $this->assertSame(['tags'], $props->calculateUnexpectedNestedPathsForProperty(
            'product',
            ['tags.0', 'name']
        ));
    }
}
