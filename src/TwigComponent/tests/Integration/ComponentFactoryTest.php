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
        self::bootKernel();

        /** @var ComponentFactory $factory */
        $factory = self::$container->get('ux.twig_component.component_factory');

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
        self::bootKernel();

        /** @var ComponentFactory $factory */
        $factory = self::$container->get('ux.twig_component.component_factory');

        /** @var ComponentB $componentA */
        $componentA = $factory->create('component_b');

        /** @var ComponentB $componentB */
        $componentB = $factory->create('component_b');

        $this->assertNotSame(spl_object_id($componentA), spl_object_id($componentB));
    }

    public function testCanGetUnmountedComponent(): void
    {
        self::bootKernel();

        /** @var ComponentFactory $factory */
        $factory = self::$container->get('ux.twig_component.component_factory');

        /** @var ComponentA $component */
        $component = $factory->get('component_a');

        $this->assertNull($component->propA);
        $this->assertNull($component->getPropB());
    }

    public function testMountCanHaveOptionalParameters(): void
    {
        self::bootKernel();

        /** @var ComponentFactory $factory */
        $factory = self::$container->get('ux.twig_component.component_factory');

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
        self::bootKernel();

        /** @var ComponentFactory $factory */
        $factory = self::$container->get('ux.twig_component.component_factory');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Symfony\UX\TwigComponent\Tests\Fixture\Component\ComponentC::mount() has a required $propA parameter. Make sure this is passed or make give a default value.');

        $factory->create('component_c');
    }

    public function testExceptionThrownIfUnableToWritePassedDataToProperty(): void
    {
        self::bootKernel();

        /** @var ComponentFactory $factory */
        $factory = self::$container->get('ux.twig_component.component_factory');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to write "service" to component "Symfony\UX\TwigComponent\Tests\Fixture\Component\ComponentA". Make sure this is a writable property or create a mount() with a $service argument.');

        $factory->create('component_a', ['propB' => 'B', 'service' => 'invalid']);
    }

    public function testTwigComponentServiceMustHaveAttribute(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Service "Symfony\UX\TwigComponent\Tests\Fixture\Service\ServiceA" is tagged as a "twig.component" but does not have a "Symfony\UX\TwigComponent\Attribute\AsTwigComponent" class attribute.');

        self::bootKernel(['environment' => 'missing_attribute']);
    }
}
