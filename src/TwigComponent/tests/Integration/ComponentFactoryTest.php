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
use Symfony\UX\TwigComponent\Tests\Fixture\Component\ComponentA;
use Symfony\UX\TwigComponent\Tests\Fixture\Component\ComponentB;
use Symfony\UX\TwigComponent\Tests\Fixture\Component\ComponentC;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComponentFactoryTest extends KernelTestCase
{
    public function testCreatedComponentsAreNotShared(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        /** @var ComponentA $componentA */
        $componentA = $factory->create('component_a', ['propA' => 'A', 'propB' => 'B']);

        /** @var ComponentA $componentB */
        $componentB = $factory->create('component_a', ['propA' => 'C', 'propB' => 'D']);

        $this->assertNotSame(spl_object_id($componentA), spl_object_id($componentB));
        $this->assertSame(spl_object_id($componentA->getService()), spl_object_id($componentB->getService()));
        $this->assertSame('A', $componentA->propA);
        $this->assertSame('B', $componentA->getPropB());
        $this->assertSame('C', $componentB->propA);
        $this->assertSame('D', $componentB->getPropB());
    }

    public function testNonAutoConfiguredCreatedComponentsAreNotShared(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        /** @var ComponentB $componentA */
        $componentA = $factory->create('component_b');

        /** @var ComponentB $componentB */
        $componentB = $factory->create('component_b');

        $this->assertNotSame(spl_object_id($componentA), spl_object_id($componentB));
    }

    public function testCanGetUnmountedComponent(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        /** @var ComponentA $component */
        $component = $factory->get('component_a');

        $this->assertNull($component->propA);
        $this->assertNull($component->getPropB());
    }

    public function testMountCanHaveOptionalParameters(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        /** @var ComponentC $component */
        $component = $factory->create('component_c', [
            'propA' => 'valueA',
            'propC' => 'valueC',
        ]);

        $this->assertSame('valueA', $component->propA);
        $this->assertNull($component->propB);
        $this->assertSame('valueC', $component->propC);

        /** @var ComponentC $component */
        $component = $factory->create('component_c', [
            'propA' => 'valueA',
            'propB' => 'valueB',
        ]);

        $this->assertSame('valueA', $component->propA);
        $this->assertSame('valueB', $component->propB);
        $this->assertSame('default', $component->propC);
    }

    public function testExceptionThrownIfRequiredMountParameterIsMissingFromPassedData(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Symfony\UX\TwigComponent\Tests\Fixture\Component\ComponentC::mount() has a required $propA parameter. Make sure this is passed or make give a default value.');

        $factory->create('component_c');
    }

    public function testExceptionThrownIfUnableToWritePassedDataToProperty(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to write "service" to component "Symfony\UX\TwigComponent\Tests\Fixture\Component\ComponentA". Make sure this is a writable property or create a mount() with a $service argument.');

        $factory->create('component_a', ['propB' => 'B', 'service' => 'invalid']);
    }

    public function testTwigComponentServiceTagMustHaveKey(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('"twig.component" tag for service "missing_key" requires a "key" attribute.');

        self::bootKernel(['environment' => 'missing_key']);
    }

    public function testCanGetConfigForComponentByName(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $this->assertSame(
            [
                'key' => 'component_a',
                'service_id' => ComponentA::class,
                'class' => ComponentA::class,
                'name' => 'component_a',
                'template' => 'components/component_a.html.twig',
            ],
            $factory->configFor('component_a')
        );
    }

    public function testCanGetConfigForComponentByObject(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $this->assertSame(
            [
                'key' => 'component_c',
                'service_id' => ComponentC::class,
                'class' => ComponentC::class,
                'name' => 'component_c',
                'template' => 'components/component_c.html.twig',
            ],
            $factory->configFor(new ComponentC())
        );
    }

    public function testCanGetConfigForComponentByClass(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $this->assertSame(
            [
                'key' => 'component_a',
                'service_id' => ComponentA::class,
                'class' => ComponentA::class,
                'name' => 'component_a',
                'template' => 'components/component_a.html.twig',
            ],
            $factory->configFor(ComponentA::class)
        );
    }

    public function testCanGetConfigForSameComponentWithDifferentName(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $this->assertSame(
            [
                'key' => 'component_d',
                'template' => 'components/custom2.html.twig',
                'service_id' => 'component_d',
                'class' => ComponentB::class,
                'name' => 'component_d',
            ],
            $factory->configFor(new ComponentB(), 'component_d')
        );
    }

    public function testCannotGetConfigForComponentIfMultipleOfSameClass(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectDeprecationMessage(sprintf('2 "%s" components registered with names "component_b, component_d". Use the $name parameter to explicitly choose one.', ComponentB::class));

        $factory->configFor(new ComponentB());
    }

    public function testCannotGetConfigByNameForNonRegisteredComponent(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown component "invalid". The registered components are: component_a, component_b, component_c, component_d');

        $factory->configFor('invalid');
    }

    public function testCannotGetConfigByClassForNonRegisteredComponent(): void
    {
        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown component class "Symfony\UX\TwigComponent\Tests\Integration\ComponentFactoryTest". The registered components are: component_a, component_b, component_c, component_d');

        $factory->configFor(self::class);
    }
}
