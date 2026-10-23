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
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\CompiledEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\ComponentTemplateFinderInterface;
use Symfony\UX\TwigComponent\Event\PreMountEvent;
use Twig\Environment;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class ComponentFactoryTest extends TestCase
{
    public function testMetadataForConfig(): void
    {
        $factory = new ComponentFactory(
            $this->createMock(ComponentTemplateFinderInterface::class),
            $this->createMock(ContainerInterface::class),
            $this->createMock(PropertyAccessorInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            ['foo' => ['key' => 'foo', 'template' => 'bar.html.twig']],
            [],
            $this->createMock(Environment::class),
        );

        $metadata = $factory->metadataFor('foo');

        $this->assertSame('foo', $metadata->getName());
        $this->assertSame('bar.html.twig', $metadata->getTemplate());
    }

    public function testMetadataForResolveAlias(): void
    {
        $factory = new ComponentFactory(
            $this->createMock(ComponentTemplateFinderInterface::class),
            $this->createMock(ContainerInterface::class),
            $this->createMock(PropertyAccessorInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            [
                'bar' => ['key' => 'bar', 'template' => 'bar.html.twig'],
                'foo' => ['key' => 'foo', 'template' => 'foo.html.twig'],
            ],
            ['Foo\\Bar' => 'bar'],
            $this->createMock(Environment::class),
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
            $this->createMock(ContainerInterface::class),
            $this->createMock(PropertyAccessorInterface::class),
            $this->createMock(EventDispatcherInterface::class),
            [],
            [],
            $this->createMock(Environment::class),
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

    public function testAListenerAddedAtRuntimeBringsBackTheEventsOfTheComponentsOutOfTheScope(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(PreMountEvent::class, static function (): void {});
        $factory = new ComponentFactory(
            $this->createStub(ComponentTemplateFinderInterface::class),
            $this->createStub(ContainerInterface::class),
            $this->createStub(PropertyAccessorInterface::class),
            $dispatcher,
            [],
            [],
            $this->createStub(Environment::class),
            [PreMountEvent::class => ['live', [], 1]],
        );
        $metadata = new ComponentMetadata(['key' => 'foo', 'template' => 'foo.html.twig']);

        $beforeAddingAListener = $factory->hasListenersFor(PreMountEvent::class, $metadata, []);
        $dispatcher->addListener(PreMountEvent::class, static function (): void {});
        $afterAddingAListener = $factory->hasListenersFor(PreMountEvent::class, $metadata, []);

        $this->assertFalse($beforeAddingAListener);
        $this->assertTrue($afterAddingAListener);
    }

    public function testCheckingTheListenersOfAComponentOutOfTheirScopeDoesNotThawACompiledDispatcher(): void
    {
        if (!class_exists(CompiledEventDispatcher::class)) {
            $this->markTestSkipped('CompiledEventDispatcher requires symfony/event-dispatcher 8.2 or higher.');
        }

        $dispatcher = new CompiledEventDispatcher(
            [PreMountEvent::class => [0 => [['live_listener', '__invoke']]]],
            $this->createStub(ContainerInterface::class),
        );
        $factory = new ComponentFactory(
            $this->createStub(ComponentTemplateFinderInterface::class),
            $this->createStub(ContainerInterface::class),
            $this->createStub(PropertyAccessorInterface::class),
            $dispatcher,
            [],
            [],
            $this->createStub(Environment::class),
            [PreMountEvent::class => ['live', [], 1]],
        );
        $metadata = new ComponentMetadata(['key' => 'foo', 'template' => 'foo.html.twig']);

        $hasListeners = $factory->hasListenersFor(PreMountEvent::class, $metadata, []);

        $this->assertFalse($hasListeners);
        // a thawed dispatcher dispatches like a compiled one, only its state tells them apart
        $this->assertNull((fn () => $this->dispatcher)->call($dispatcher));
    }
}
