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
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentTemplateFinderInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class ComponentFactoryTest extends TestCase
{
    public function testMetadataForConfig(): void
    {
        $factory = new ComponentFactory(
            $this->createMock(ComponentTemplateFinderInterface::class),
            $this->createMock(ServiceLocator::class),
            $this->createMock(PropertyAccessorInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            ['foo' => ['key' => 'foo', 'template' => 'bar.html.twig']],
            []
        );

        $metadata = $factory->metadataFor('foo');

        $this->assertSame('foo', $metadata->getName());
        $this->assertSame('bar.html.twig', $metadata->getTemplate());
    }

    public function testMetadataForResolveAlias(): void
    {
        $factory = new ComponentFactory(
            $this->createMock(ComponentTemplateFinderInterface::class),
            $this->createMock(ServiceLocator::class),
            $this->createMock(PropertyAccessorInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            [
                'bar' => ['key' => 'bar', 'template' => 'bar.html.twig'],
                'foo' => ['key' => 'foo', 'template' => 'foo.html.twig'],
            ],
            ['Foo\\Bar' => 'bar'],
        );

        $metadata = $factory->metadataFor('Foo\\Bar');

        $this->assertSame('bar', $metadata->getName());
        $this->assertSame('bar.html.twig', $metadata->getTemplate());
    }

    public function testMetadataForReuseAnonymousConfig(): void
    {
        $templateFinder = $this->createMock(ComponentTemplateFinderInterface::class);
        $templateFinder->expects($this->atLeastOnce())
            ->method('findAnonymousComponentTemplate')
            ->with('foo')
            ->willReturnOnConsecutiveCalls('foo.html.twig', 'bar.html.twig', 'bar.html.twig');

        $factory = new ComponentFactory(
            $templateFinder,
            $this->createMock(ServiceLocator::class),
            $this->createMock(PropertyAccessorInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            [],
            []
        );

        $metadata = $factory->metadataFor('foo');
        $this->assertSame('foo', $metadata->getName());
        $this->assertSame('foo.html.twig', $metadata->getTemplate());

        $metadata = $factory->metadataFor('foo');
        $this->assertSame('foo', $metadata->getName());
        $this->assertSame('foo.html.twig', $metadata->getTemplate());

        $metadata = $factory->metadataFor('foo');
        $this->assertSame('foo', $metadata->getName());
        $this->assertSame('foo.html.twig', $metadata->getTemplate());
    }
}
