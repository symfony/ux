<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\BasicComponent;
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\ComponentA;
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\ComponentB;
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\ComponentC;
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\WithSlots;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentFactoryTest extends KernelTestCase
{
    public function testCreatedComponentsAreNotShared(): void
    {
        /** @var ComponentA $componentA */
        $componentA = $this->createComponent('component_a', ['propA' => 'A', 'propB' => 'B']);

        /** @var ComponentA $componentB */
        $componentB = $this->createComponent('component_a', ['propA' => 'C', 'propB' => 'D']);

        $this->assertNotSame(spl_object_id($componentA), spl_object_id($componentB));
        $this->assertSame(spl_object_id($componentA->getService()), spl_object_id($componentB->getService()));
        $this->assertSame('A', $componentA->propA);
        $this->assertSame('B', $componentA->getPropB());
        $this->assertSame('C', $componentB->propA);
        $this->assertSame('D', $componentB->getPropB());
    }

    public function testNonAutoConfiguredCreatedComponentsAreNotShared(): void
    {
        /** @var ComponentB $componentA */
        $componentA = $this->createComponent('component_b');

        /** @var ComponentB $componentB */
        $componentB = $this->createComponent('component_b');

        $this->assertNotSame(spl_object_id($componentA), spl_object_id($componentB));
    }

    public function testCanGetUnmountedComponent(): void
    {
        /** @var ComponentA $component */
        $component = $this->factory()->get('component_a');

        $this->assertNull($component->propA);
        $this->assertNull($component->getPropB());
    }

    public function testMountCanHaveOptionalParameters(): void
    {
        /** @var ComponentC $component */
        $component = $this->createComponent('component_c', [
            'propA' => 'valueA',
            'propC' => 'valueC',
        ]);

        $this->assertSame('valueA', $component->propA);
        $this->assertNull($component->propB);
        $this->assertSame('valueC', $component->propC);

        /** @var ComponentC $component */
        $component = $this->createComponent('component_c', [
            'propA' => 'valueA',
            'propB' => 'valueB',
        ]);

        $this->assertSame('valueA', $component->propA);
        $this->assertSame('valueB', $component->propB);
        $this->assertSame('default', $component->propC);
    }

    public function testExceptionThrownIfRequiredMountParameterIsMissingFromPassedData(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Symfony\UX\TwigComponent\Tests\Fixtures\Component\ComponentC::mount() has a required $propA parameter. Make sure this is passed or make give a default value.');

        $this->createComponent('component_c');
    }

    public function testStringableObjectCanBePassedToComponent(): void
    {
        $attributes = $this->factory()->create('component_a', ['propB' => 'B', 'data-item-id-param' => new class() {
            public function __toString(): string
            {
                return 'test';
            }
        }])->getAttributes()->all();

        self::assertSame(['data-item-id-param' => 'test'], $attributes);
    }

    public function testTwigComponentServiceTagWithoutKeyUsesShortClassName(): void
    {
        // boots ComponentB, but with no key on the tag
        self::bootKernel(['environment' => 'missing_key']);
        $component = $this->createComponent('ComponentB');
        self::assertInstanceOf(ComponentB::class, $component);
    }

    public function testTwigComponentServiceTagWithoutKeyButCollissionCausesAnException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Failed creating the "Symfony\UX\TwigComponent\Tests\Fixtures\Component\ComponentB" component with the automatic name "ComponentB": another component already has this name. To fix this, give the component an explicit name (hint: using "ComponentB" will override the existing component).');

        self::bootKernel(['environment' => 'missing_key_with_collision']);
        $component = $this->createComponent('ComponentB');
        self::assertInstanceOf(ComponentB::class, $component);
    }

    /**
     * @group legacy
     */
    public function testLegacyAutoNaming(): void
    {
        self::bootKernel(['environment' => 'legacy_autonaming']);
        $component = $this->createComponent('BasicComponent');
        self::assertInstanceOf(BasicComponent::class, $component);
    }

    /**
     * @group legacy
     */
    public function testLegacyAnonymous(): void
    {
        self::bootKernel(['environment' => 'legacy_anonymous']);

        $this->expectException(\InvalidArgumentException::class);
        $this->factory()->metadataFor('anonymous:AButton');

        $this->expectException(\InvalidArgumentException::class);
        $this->factory()->metadataFor('AButton');

        $metadata = $this->factory()->metadataFor('foo:bar:baz');
        $this->assertSame('components/components/foo:bar:baz.html.twig', $metadata->getTemplate());
    }

    public function testAnonymous(): void
    {
        self::bootKernel(['environment' => 'anonymous_directory']);

        $this->expectException(\InvalidArgumentException::class);
        $this->factory()->metadataFor('anonymous:AButton');

        $metadata = $this->factory()->metadataFor('AButton');
        $this->assertSame('components/anonymous/AButton.html.twig', $metadata->getTemplate());

        $this->expectException(\InvalidArgumentException::class);
        $this->factory()->metadataFor('foo:bar:baz');
    }

    public function testAutoNamingInSubDirectory(): void
    {
        $metadata = $this->factory()->metadataFor('SubDirectory:ComponentInSubDirectory');
        $this->assertSame('SubDirectory:ComponentInSubDirectory', $metadata->getName());
        $this->assertSame('components/SubDirectory/ComponentInSubDirectory.html.twig', $metadata->getTemplate());
    }

    public function testAutoNamingWithNamePrefixAndDirectory(): void
    {
        $metadata = $this->factory()->metadataFor('AcmePrefix:AcmeRootComponent');
        $this->assertSame('AcmePrefix:AcmeRootComponent', $metadata->getName());
        $this->assertSame('acme_components/AcmeRootComponent.html.twig', $metadata->getTemplate());

        $metadata = $this->factory()->metadataFor('AcmePrefix:AcmeSubDir:AcmeOtherComponent');
        $this->assertSame('AcmePrefix:AcmeSubDir:AcmeOtherComponent', $metadata->getName());
        $this->assertSame('acme_components/AcmeSubDir/AcmeOtherComponent.html.twig', $metadata->getTemplate());
    }

    public function testAutoNamingWithNamePrefixOnly(): void
    {
        self::bootKernel(['environment' => 'no_template_directory']);
        $metadata = $this->factory()->metadataFor('AcmePrefix:AcmeRootComponent');
        $this->assertSame('AcmePrefix:AcmeRootComponent', $metadata->getName());
        // the "AcmePrefix" is never part of the template directory name
        // if the user wants it to be, they can set the "template_directory" option
        $this->assertSame('components/AcmeRootComponent.html.twig', $metadata->getTemplate());
    }

    public function testCanGetMetadataForComponentByName(): void
    {
        $metadata = $this->factory()->metadataFor('component_a');

        $this->assertSame('components/component_a.html.twig', $metadata->getTemplate());
        $this->assertSame('component_a', $metadata->getName());
        $this->assertSame(ComponentA::class, $metadata->getServiceId());
        $this->assertSame(ComponentA::class, $metadata->getClass());
    }

    public function testCanGetMetadataForSameComponentWithDifferentName(): void
    {
        $metadata = $this->factory()->metadataFor('component_d');

        $this->assertSame('components/custom2.html.twig', $metadata->getTemplate());
        $this->assertSame('component_d', $metadata->getName());
        $this->assertSame('component_d', $metadata->getServiceId());
        $this->assertSame(ComponentB::class, $metadata->getClass());
    }

    public function testCannotGetConfigByNameForNonRegisteredComponent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown component "tabl". Did you mean this: "table"?');

        $this->factory()->metadataFor('tabl');
    }

    /**
     * @testWith ["tabl", "Unknown component \"tabl\". Did you mean this: \"table\"?"]
     *           ["Basic", "Unknown component \"Basic\". Did you mean this: \"BasicComponent\"?"]
     *           ["basic", "Unknown component \"basic\". Did you mean this: \"BasicComponent\"?"]
     *           ["with", "Unknown component \"with\". Did you mean one of these: \"with_attributes\", \"with_exposed_variables\", \"WithSlots\"?"]
     *           ["anonAnon", "Unknown component \"anonAnon\". And no matching anonymous component template was found."]
     */
    public function testCannotGetInvalidComponent(string $name, string $expectedExceptionMessage): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $this->factory()->get($name);
    }

    public function testInputPropsStoredOnMountedComponent(): void
    {
        $mountedComponent = $this->factory()->create('component_a', ['propA' => 'A', 'propB' => 'B']);
        $this->assertSame(['propA' => 'A', 'propB' => 'B'], $mountedComponent->getInputProps());
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testGetComponentWithClassName(): void
    {
        $factory = $this->factory();

        $factory->create(WithSlots::class);
        $factory->get(WithSlots::class);
        $factory->metadataFor(WithSlots::class);
    }

    private function factory(): ComponentFactory
    {
        return self::getContainer()->get('ux.twig_component.component_factory');
    }

    private function createComponent(string $name, array $data = []): object
    {
        return $this->factory()->create($name, $data)->getComponent();
    }
}
