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
use Symfony\Component\TypeInfo\Type;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Metadata\LegacyLivePropMetadata;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Metadata\LivePropMetadata;
use Twig\Environment;

final class LiveComponentHydratorTest extends TestCase
{
    public function testConstructWithEmptySecret()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A non-empty secret is required.');

        new LiveComponentHydrator(
            [],
            $this->createMock(PropertyAccessorInterface::class),
            $this->createMock(LiveComponentMetadataFactory::class),
            $this->createMock(NormalizerInterface::class),
            '',
            $this->createMock(Environment::class),
        );
    }

    public function testItCanHydrateWithNullValues()
    {
        // BC layer when "symfony/type-info" is not available
        if (!class_exists(Type::class)) {
            $hydrator = new LiveComponentHydrator(
                [],
                $this->createMock(PropertyAccessorInterface::class),
                $this->createMock(LiveComponentMetadataFactory::class),
                new Serializer(normalizers: [new ObjectNormalizer()]),
                'foo',
                $this->createMock(Environment::class),
            );

            $hydratedValue = $hydrator->hydrateValue(
                null,
                new LegacyLivePropMetadata('foo', new LiveProp(useSerializerForHydration: true), typeName: Foo::class, isBuiltIn: false, allowsNull: true, collectionValueType: null),
                parentObject: new \stdClass() // not relevant in this test
            );

            self::assertNull($hydratedValue);
        } else {
            $hydrator = new LiveComponentHydrator(
                [],
                $this->createMock(PropertyAccessorInterface::class),
                $this->createMock(LiveComponentMetadataFactory::class),
                new Serializer(normalizers: [new ObjectNormalizer()]),
                'foo',
                $this->createMock(Environment::class),
            );

            $hydratedValue = $hydrator->hydrateValue(
                null,
                new LivePropMetadata('foo', new LiveProp(useSerializerForHydration: true), Type::nullable(Type::object(Foo::class))),
                parentObject: new \stdClass() // not relevant in this test
            );

            self::assertNull($hydratedValue);
        }
    }
}

class Foo
{
    public function __construct(private int $id)
    {
    }
}
