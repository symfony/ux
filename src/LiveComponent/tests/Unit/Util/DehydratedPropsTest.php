<?php

namespace Symfony\UX\LiveComponent\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\Util\DehydratedProps;

class DehydratedPropsTest extends TestCase
{
    /**
     * @dataProvider getDataIsBuiltCorrectlyTestCases
     */
    public function testDataIsBuiltCorrectly(string $fieldName, mixed $identifier, array $writablePathValues, mixed $expectedArray): void
    {
        $props = new DehydratedProps();
        // simple scalar
        $props->addIdentifierValue($fieldName, $identifier);

        foreach ($writablePathValues as $path => $value) {
            $props->addWritablePathValue($fieldName, $path, $value);
        }

        $actualAsArray = $props->toArray();
        $this->assertSame($expectedArray, $actualAsArray[$fieldName]);

        // now reverse the process
        $propsFromArray = DehydratedProps::createFromArray($actualAsArray);
        $this->assertEquals($props, $propsFromArray);
    }

    public function getDataIsBuiltCorrectlyTestCases(): iterable
    {
        yield 'identity_string_no_writable_paths' => [
            'firstName',
            'Ryan',
            [],
            'Ryan',
        ];

        yield 'identity_string_with_writable_paths' => [
            'product',
            '5',
            ['name' => 'marshmallows'],
            ['@id' => '5', 'name' => 'marshmallows'],
        ];

        yield 'identity_array_no_writable_paths' => [
            'product',
            ['name' => 'marshmallows'],
            [],
            ['name' => 'marshmallows'],
        ];

        yield 'identity_array_with_writable_paths' => [
            'product',
            ['name' => 'marshmallows', 'description' => 'delicious'],
            ['price' => '25.99', 'description' => 'delicious'],
            [
                '@id' => [
                    'name' => 'marshmallows',
                    'description' => 'delicious',
                ],
                'price' => '25.99',
                'description' => 'delicious',
            ],
        ];

        yield 'identity_with_deep_paths' => [
            'product',
            '5',
            [
                'name' => 'marshmallows',
                'category.title' => 'campfire food',
            ],
            [
                '@id' => '5',
                'name' => 'marshmallows',
                'category' => [
                    'title' => 'campfire food',
                ],
            ],
        ];

        yield 'identity_indexed_array_of_strings' => [
            'tags',
            ['foo', 'bar'],
            [],
            [
                0 => 'foo',
                1 => 'bar',
            ],
        ];

        yield 'identity_indexed_array_of_objects' => [
            'tags',
            [['name' => 'foo'], ['name' => 'bar']],
            [],
            [['name' => 'foo'], ['name' => 'bar']],
        ];

        yield 'identity_indexed_array_of_objects_with_writable' => [
            'tags',
            [['name' => 'foo'], ['name' => 'bar']],
            ['0.name' => 'foo'],
            [
                '@id' => [['name' => 'foo'], ['name' => 'bar']],
                ['name' => 'foo'],
            ],
        ];
    }

    public function testRemoveIdentifierValue(): void
    {
        $props = new DehydratedProps();
        $props->addIdentifierValue('firstName', 'Ryan');
        $props->addIdentifierValue('lastName', 'Weaver');
        $props->removeIdentifierValue('firstName');
        $this->assertSame(['lastName' => 'Weaver'], $props->toArray());
    }

    public function testGetAndHasIdentifierValue(): void
    {
        $props = new DehydratedProps();
        $props->addIdentifierValue('firstName', 'Ryan');
        $props->addIdentifierValue('lastName', 'Weaver');
        $this->assertTrue($props->hasIdentifierValue('firstName'));
        $this->assertFalse($props->hasIdentifierValue('middleName'));
        $this->assertSame('Ryan', $props->getIdentifierValue('firstName'));
        $this->assertNull($props->getIdentifierValue('middleName'));
    }

    public function testGetAndHasWritablePathValue()
    {
        $props = new DehydratedProps();
        $props->addIdentifierValue('student', '11');
        $props->addWritablePathValue('student', 'firstName', 'Ryan');
        $props->addWritablePathValue('student', 'lastName', 'Weaver');

        $this->assertTrue($props->hasWritablePathValue('student', 'firstName'));
        $this->assertFalse($props->hasWritablePathValue('student', 'middleName'));
        $this->assertSame('Ryan', $props->getWritablePathValue('student', 'firstName'));

        $props->addIdentifierValue('product', '9');
        $props->addWritablePathValue('product', 'category.name', 'Campfire Food');
        $this->assertTrue($props->hasWritablePathValue('product', 'category.name'));
        $this->assertSame('Campfire Food', $props->getWritablePathValue('product', 'category.name'));
    }

    public function testCreateFromFlatArray(): void
    {
        $actual = DehydratedProps::createFromUpdatedArray([
            'isPublic' => true,
            'student' => 10,
            'student.firstName' => 'Ryan',
            'student.lastName' => 'Weaver',
            'product.name' => 'Smores',
            'product.category.name' => 'Campfire Food',
        ]);

        $expected = new DehydratedProps(allowMissingIdentifierValues: true);
        $expected->addIdentifierValue('isPublic', true);
        $expected->addIdentifierValue('student', 10);
        $expected->addWritablePathValue('student', 'firstName', 'Ryan');
        $expected->addWritablePathValue('student', 'lastName', 'Weaver');
        $expected->addWritablePathValue('product', 'name', 'Smores');
        $expected->addWritablePathValue('product', 'category.name', 'Campfire Food');

        $this->assertEquals($expected, $actual);
    }

    public function testGetWritablePathsForProperty(): void
    {
        // test DehydratedProps::getWritablePathsForProperty()
        $props = DehydratedProps::createFromUpdatedArray([
            'options.show' => 'Arrested development',
            'options.characters.main' => 'Michael Bluth',
        ]);

        $actual = $props->getWritablePathsForProperty('options');
        $this->assertSame(['show', 'characters.main'], $actual);
    }

    public function testCalculateUnexpectedWritablePaths(): void
    {
        $props = DehydratedProps::createFromUpdatedArray([
            'product.tags' => ['pretzels', 'nonsense'],
            'product.name' => 'bananas',
        ]);

        $this->assertEmpty($props->calculateUnexpectedWritablePathsForProperty(
            'product',
            ['tags', 'name']
        ));
        $this->assertEmpty($props->calculateUnexpectedWritablePathsForProperty(
            'product',
            ['tags', 'name', 'other']
        ));
        $this->assertSame(['name'], $props->calculateUnexpectedWritablePathsForProperty(
            'product',
            ['tags']
        ));
        $this->assertSame(['tags.1'], $props->calculateUnexpectedWritablePathsForProperty(
            'product',
            ['name', 'tags.0']
        ));
    }
}
