<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\DehydratedComponent;

class DehydratedComponentTest extends TestCase
{
    public function testCanGetAndSetBasicData(): void
    {
        $actualProps = ['prop1' => 'prop1value'];
        $actualData = ['data1' => 'data1value'];
        $actualAttributes = ['attribute1' => 'attribute1value'];
        $dehydrated = new DehydratedComponent(
            $actualProps,
            $actualData,
            [],
            $actualAttributes,
            'secret'
        );
        $expectedChecksum = 'Wuq5ei+IGuP5zDgmcXH1iwr4ZKG63OQE+YbDXQPGmjs=';

        $this->assertEquals(
            $actualProps + ['_checksum' => $expectedChecksum, '_attributes' => $actualAttributes],
            $dehydrated->getProps()
        );

        $this->assertEquals($actualData, $dehydrated->getData());
        $this->assertEquals($actualAttributes, $dehydrated->getAttributes());
        $this->assertEquals(
            $actualProps + $actualData + ['_checksum' => $expectedChecksum, '_attributes' => $actualAttributes],
            $dehydrated->all()
        );
        $this->assertEquals(
            $actualProps + $actualData,
            $dehydrated->allForComponent()
        );
    }

    public function testItHandlesExposedData(): void
    {
        $actualProps = ['title' => 'foo title', 'user' => 5];
        $actualData = ['content' => 'foo content'];
        $exposedData = ['user' => ['firstName' => 'Ryan']];
        $dehydrated = new DehydratedComponent(
            $actualProps,
            $actualData,
            $exposedData,
            [],
            'secret'
        );

        $this->assertEquals([
            'title' => 'foo title',
            'content' => 'foo content',
            'user' => ['_id' => 5, 'firstName' => 'Ryan'],
        ], $dehydrated->allForComponent());

        $all = $dehydrated->all();
        unset($all['_checksum']);
        $this->assertEquals([
            'title' => 'foo title',
            'content' => 'foo content',
            'user' => ['_id' => 5, 'firstName' => 'Ryan'],
        ], $all);
    }

    public function testItHandlesExposedDataWhereIdentifierIsAlsoData(): void
    {
        $actualProps = ['title' => 'foo title'];
        $actualData = ['content' => 'foo content', 'user' => 5];
        $exposedData = ['user' => ['firstName' => 'Ryan']];
        $dehydrated = new DehydratedComponent(
            $actualProps,
            $actualData,
            $exposedData,
            [],
            'secret'
        );

        $this->assertEquals([
            'title' => 'foo title',
            'content' => 'foo content',
            'user' => ['_id' => 5, 'firstName' => 'Ryan'],
        ], $dehydrated->allForComponent());

        $all = $dehydrated->all();
        unset($all['_checksum']);
        $this->assertEquals([
            'title' => 'foo title',
            'content' => 'foo content',
            'user' => ['_id' => 5, 'firstName' => 'Ryan'],
        ], $all);
    }

    public function testCreateFromCombinedData(): void
    {
        $dehydrated = DehydratedComponent::createFromCombinedData(
            [
                'prop1' => 'prop1value',
                'data1' => 'data1value',
                '_checksum' => 'the_checksum',
                '_attributes' => ['attribute1' => 'attribute1value'],
            ],
            ['prop1'],
            'secret'
        );

        $this->assertEquals(['data1' => 'data1value'], $dehydrated->getData());
        $this->assertEquals(['attribute1' => 'attribute1value'], $dehydrated->getAttributes());
        $all = $dehydrated->all();
        unset($all['_checksum']);
        $this->assertEquals([
            'prop1' => 'prop1value',
            'data1' => 'data1value',
            '_attributes' => ['attribute1' => 'attribute1value'],
        ], $all);
    }

    public function testCreateFromCombinedDataWithDeepExposedArrays(): void
    {
        $dehydrated = DehydratedComponent::createFromCombinedData(
            [
                'title' => 'titlevalue',
                'content' => 'contentvalue',
                // "user" will be a prop. Any other keys (e.g. firstName) must then be "exposed", hence data
                'user' => ['_id' => 5, 'firstName' => 'Ryan'],
            ],
            ['title', 'user'],
            'secret'
        );

        $this->assertEquals(['content' => 'contentvalue', 'user' => ['firstName' => 'Ryan']], $dehydrated->getData());
        $props = $dehydrated->getProps();
        unset($props['_checksum']);
        $this->assertEquals([
            'title' => 'titlevalue',
            'user' => ['_id' => 5],
        ], $props);
    }

    public function testIsChecksumValid(): void
    {
        $dehydrated = new DehydratedComponent(['props1' => 'props1value'], ['data1' => 'data1value'], [], [], 'secret');

        $all = $dehydrated->all();
        $this->assertFalse($dehydrated->isChecksumValid([]), 'missing checksum');
        $this->assertFalse($dehydrated->isChecksumValid(['_checksum' => 'wrong']));
        $this->assertTrue($dehydrated->isChecksumValid(['_checksum' => $all['_checksum']]));
    }

    public function testHas(): void
    {
        $dehydrated = new DehydratedComponent(
            ['prop1' => 'propvalue1'],
            ['user' => 5],
            ['user' => ['firstName' => 'Ryan']],
            [],
            'secret'
        );

        $this->assertFalse($dehydrated->has('made up'));
        $this->assertTrue($dehydrated->has('prop1'));
        $this->assertTrue($dehydrated->has('user'));
        $this->assertFalse($dehydrated->has('user.firstName'), 'No support for fanciness');
    }

    public function testGet(): void
    {
        $dehydrated = new DehydratedComponent(
            ['prop1' => 'propvalue1'],
            ['user' => 5],
            ['user' => ['firstName' => 'Ryan']],
            [],
            'secret'
        );

        $this->assertSame('propvalue1', $dehydrated->get('prop1'));
        $this->assertSame(5, $dehydrated->get('user'), 'only the main value is returned');
    }

    public function testGetExposed(): void
    {
        $dehydrated = new DehydratedComponent(
            ['prop1' => 'propvalue1'],
            ['user' => 5],
            ['user' => ['firstName' => 'Ryan']],
            [],
            'secret'
        );

        $this->assertEquals(['firstName' => 'Ryan'], $dehydrated->getExposed('user'));
    }
}
