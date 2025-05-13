<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Metadata\LivePropMetadata;

final class LiveComponentHydratorTest extends TestCase
{
    public function testConstructWithEmptySecret(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A non-empty secret is required.');

        new LiveComponentHydrator(
            [],
            $this->createMock(PropertyAccessorInterface::class),
            $this->createMock(LiveComponentMetadataFactory::class),
            $this->createMock(NormalizerInterface::class),
            '',
        );
    }

    public function testItCanHydrateWithNullValues()
    {
        $hydrator = new LiveComponentHydrator(
            [],
            $this->createMock(PropertyAccessorInterface::class),
            $this->createMock(LiveComponentMetadataFactory::class),
            new Serializer(normalizers: [new ObjectNormalizer()]),
            'foo',
        );

        $hydratedValue = $hydrator->hydrateValue(
            null,
            new LivePropMetadata('foo', new LiveProp(useSerializerForHydration: true), typeName: Foo::class, isBuiltIn: false, allowsNull: true, collectionValueType: null),
            parentObject: new \stdClass() // not relevant in this test
        );

        self::assertNull($hydratedValue);
    }
}

class Foo
{
    public function __construct(private int $id)
    {
    }
}
