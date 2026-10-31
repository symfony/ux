<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\ComponentProperties;

final class ComponentPropertiesTest extends TestCase
{
    public function testUsesWarmedMetadata(): void
    {
        $cache = new ArrayAdapter();
        $cache->save($cache->getItem('ux.twig_component.component_properties')->set([
            ComponentWithExposedProperty::class => [
                // Differs from what reflection finds, to tell the cached metadata apart
                'properties' => ['value' => ['name' => 'fromCache', 'getter' => null]],
                'methods' => [],
            ],
        ]));

        $properties = new ComponentProperties(PropertyAccess::createPropertyAccessor(), [ComponentWithExposedProperty::class => null], $cache);

        $this->assertSame(['fromCache' => 'foo'], $properties->getProperties(new ComponentWithExposedProperty()));
    }

    public function testLoadsMetadataOfClassesMissingFromTheCache(): void
    {
        $cache = new ArrayAdapter();
        $cache->save($cache->getItem('ux.twig_component.component_properties')->set([]));

        $properties = new ComponentProperties(PropertyAccess::createPropertyAccessor(), [ComponentWithExposedProperty::class => null], $cache);

        $this->assertSame(['exposed' => 'foo'], $properties->getProperties(new ComponentWithExposedProperty()));
    }

    public function testWarmupReplacesStaleMetadata(): void
    {
        $cache = new ArrayAdapter();
        $cache->save($cache->getItem('ux.twig_component.component_properties')->set([
            ComponentWithExposedProperty::class => [
                'properties' => ['value' => ['name' => 'fromCache', 'getter' => null]],
                'methods' => [],
            ],
        ]));
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $classMetadata = [ComponentWithExposedProperty::class => null];

        $warmer = new ComponentProperties($propertyAccessor, $classMetadata, $cache);
        $warmer->warmup();
        $properties = new ComponentProperties($propertyAccessor, $classMetadata, $cache);

        $this->assertSame(['exposed' => 'foo'], $properties->getProperties(new ComponentWithExposedProperty()));
    }
}

final class ComponentWithExposedProperty
{
    #[ExposeInTemplate('exposed')]
    private string $value = 'foo';

    public function getValue(): string
    {
        return $this->value;
    }
}
