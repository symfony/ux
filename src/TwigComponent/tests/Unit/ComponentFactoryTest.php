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
use Symfony\Component\PropertyAccess\Exception\InvalidTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyWriteInfo;
use Symfony\Component\PropertyInfo\PropertyWriteInfoExtractorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\ComponentTemplateFinderInterface;
use Symfony\UX\TwigComponent\Event\PreMountEvent;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

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

    public function testMountWritesAPublicPropertyWithoutThePropertyAccessor(): void
    {
        $component = new class {
            public string $title = '';
        };
        $accessorWriteInfoExtractor = new class(new ReflectionExtractor()) implements PropertyWriteInfoExtractorInterface {
            public array $adderRemoverContexts = [];

            public function __construct(private ReflectionExtractor $extractor)
            {
            }

            public function getWriteInfo(string $class, string $property, array $context = []): ?PropertyWriteInfo
            {
                $this->adderRemoverContexts[] = $context['enable_adder_remover_extraction'];

                return $this->extractor->getWriteInfo($class, $property, $context);
            }
        };
        $accessor = new PropertyAccessor(PropertyAccessor::MAGIC_GET | PropertyAccessor::MAGIC_SET, PropertyAccessor::THROW_ON_INVALID_PROPERTY_PATH, null, null, $accessorWriteInfoExtractor);

        $this->mountWithAccessor($component, ['title' => 'Hello'], $accessor);

        $this->assertSame('Hello', $component->title);
        // isWritable() asks for the write info of an array value; setValue() would ask for the one of a string
        $this->assertSame([true], $accessorWriteInfoExtractor->adderRemoverContexts);
    }

    public function testMountPrefersASetterOverAPublicProperty(): void
    {
        $component = new class {
            public string $title = '';

            public function setTitle(string $title): void
            {
                $this->title = strtoupper($title);
            }
        };

        $this->mountWithAccessor($component, ['title' => 'Hello']);

        $this->assertSame('HELLO', $component->title);
    }

    public function testMountUsesTheAdderAndRemoverForAnIterableValue(): void
    {
        $component = new class {
            public array $tags = ['old'];

            public function addTag(string $tag): void
            {
                $this->tags[] = strtoupper($tag);
            }

            public function removeTag(string $tag): void
            {
                $this->tags = array_values(array_diff($this->tags, [$tag]));
            }
        };

        $this->mountWithAccessor($component, ['tags' => ['a', 'b']]);

        $this->assertSame(['A', 'B'], $component->tags);
    }

    public function testMountThrowsTheInvalidTypeExceptionOfThePropertyAccessor(): void
    {
        $component = new class {
            public int $count = 0;
        };

        $this->expectException(InvalidTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "int", "string" given at property path "count".');

        $this->mountWithAccessor($component, ['count' => 'many']);
    }

    public function testMountConvertsADateTimeForADateTimeImmutableProperty(): void
    {
        $component = new class {
            public ?\DateTimeImmutable $date = null;
        };

        $this->mountWithAccessor($component, ['date' => new \DateTime('2024-01-02 03:04:05')]);

        $this->assertInstanceOf(\DateTimeImmutable::class, $component->date);
        $this->assertSame('2024-01-02 03:04:05', $component->date->format('Y-m-d H:i:s'));
    }

    public function testMountRunsTheSetHookOfAPropertyOnceWhenItFails(): void
    {
        $component = new class {
            public int $calls = 0;

            public int $count = 0 {
                set(int|string $value) {
                    ++$this->calls;
                    $this->count = $value;
                }
            }
        };

        try {
            $this->mountWithAccessor($component, ['count' => 'many']);
            $this->fail('The write should fail.');
        } catch (\TypeError|InvalidTypeException) {
        }

        $this->assertSame(1, $component->calls);
    }

    public function testMountWritesThroughADecoratedPropertyAccessor(): void
    {
        $component = new class {
            public string $title = '';
        };
        $accessor = $this->createMock(PropertyAccessorInterface::class);
        $accessor->method('isWritable')->willReturn(true);
        $accessor->expects($this->once())->method('setValue')->with($component, 'title', 'Hello');

        $this->mountWithAccessor($component, ['title' => 'Hello'], $accessor);
    }

    public function testMountWritesAPropertyWithAnIterableThenWithAScalar(): void
    {
        $component = new class {
            public array|string $tags = [];

            public function addTag(string $tag): void
            {
                $this->tags[] = strtoupper($tag);
            }

            public function removeTag(string $tag): void
            {
            }
        };
        $factory = $this->createFactoryWithAccessor();
        $metadata = new ComponentMetadata(['key' => 'foo', 'template' => 'foo.html.twig', 'service_id' => 'foo']);

        $factory->mountFromObject($component, ['tags' => ['a']], $metadata);
        $this->assertSame(['A'], $component->tags);

        $factory->mountFromObject($component, ['tags' => 'b'], $metadata);
        $this->assertSame('b', $component->tags);
    }

    public function testMountRethrowsATypeErrorThatIsNotAboutTheTypeOfTheValue(): void
    {
        $component = new class {
            public mixed $value;

            public function __construct()
            {
                $this->value = new class {
                    public function __destruct()
                    {
                        throw new \TypeError('Thrown by a destructor.');
                    }
                };
            }
        };

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Thrown by a destructor.');

        $this->mountWithAccessor($component, ['value' => 'new']);
    }

    private function mountWithAccessor(object $component, array $data, ?PropertyAccessorInterface $accessor = null): void
    {
        $this->createFactoryWithAccessor($accessor)->mountFromObject($component, $data, new ComponentMetadata(['key' => 'foo', 'template' => 'foo.html.twig', 'service_id' => 'foo']));
    }

    private function createFactoryWithAccessor(?PropertyAccessorInterface $accessor = null): ComponentFactory
    {
        $extractor = new ReflectionExtractor();

        return new ComponentFactory(
            $this->createStub(ComponentTemplateFinderInterface::class),
            $this->createStub(ContainerInterface::class),
            $accessor ?? new PropertyAccessor(PropertyAccessor::MAGIC_GET | PropertyAccessor::MAGIC_SET, PropertyAccessor::THROW_ON_INVALID_PROPERTY_PATH, null, $extractor, $extractor),
            new EventDispatcher(),
            [],
            [],
            new Environment(new ArrayLoader()),
            [],
            $extractor,
        );
    }
}
