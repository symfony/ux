<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Integration\Metadata;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Metadata\LivePropMetadata;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component1;

final class LiveComponentMetadataFactoryTest extends KernelTestCase
{
    public function testReadsTheLivePropsWrittenAtWarmup(): void
    {
        $cache = new ArrayAdapter();
        $this->createFactory($cache)->warmUp([Component1::class]);
        $propertyTypeExtractor = $this->createMock(PropertyTypeExtractorInterface::class);
        $propertyTypeExtractor->expects($this->never())->method($this->anything());
        $factory = $this->createFactory($cache, $propertyTypeExtractor);

        $metadata = $factory->getMetadata('component1');

        $this->assertSame(['prop1', 'prop2', 'prop3'], $this->propNames($metadata->getAllLivePropsMetadata(null)));
    }

    public function testFindsTheLivePropsOfAClassThatWasNotWarmedUp(): void
    {
        $cache = new ArrayAdapter();
        $factory = $this->createFactory($cache);

        $metadata = $factory->getMetadata('component1');

        $this->assertSame(['prop1', 'prop2', 'prop3'], $this->propNames($metadata->getAllLivePropsMetadata(null)));
        $this->assertSame([], array_filter($cache->getValues()));
    }

    public function testWarmUpSkipsAComponentWithInvalidLiveProps(): void
    {
        $cache = new ArrayAdapter();
        $invalidComponent = new class {
            #[LiveProp]
            public int|string $unionProp = 0;
        };
        $factory = $this->createFactory($cache);

        $factory->warmUp([$invalidComponent::class, Component1::class]);

        $this->assertCount(1, array_filter($cache->getValues()));
    }

    private function createFactory(CacheItemPoolInterface $cache, ?PropertyTypeExtractorInterface $propertyTypeExtractor = null): LiveComponentMetadataFactory
    {
        return new LiveComponentMetadataFactory(
            self::getContainer()->get('ux.twig_component.component_factory'),
            $propertyTypeExtractor ?? self::getContainer()->get('property_info'),
            self::getContainer()->get('type_info.resolver'),
            $cache,
        );
    }

    /**
     * @param iterable<LivePropMetadata> $livePropsMetadata
     *
     * @return list<string>
     */
    private function propNames(iterable $livePropsMetadata): array
    {
        $names = [];
        foreach ($livePropsMetadata as $livePropMetadata) {
            $names[] = $livePropMetadata->getName();
        }

        return $names;
    }
}
