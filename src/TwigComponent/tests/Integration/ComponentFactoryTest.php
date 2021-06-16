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
    /**
     * @test
     */
    public function created_components_are_not_shared(): void
    {
        self::bootKernel();

        /** @var ComponentFactory $factory */
        $factory = self::$container->get(ComponentFactory::class);

        /** @var ComponentA $componentA */
        $componentA = $factory->create('component_a', ['propA' => 'A', 'propB' => 'B']);

        /** @var ComponentA $componentB */
        $componentB = $factory->create('component_a', ['propA' => 'C', 'propB' => 'D']);

        $this->assertNotSame(\spl_object_id($componentA), \spl_object_id($componentB));
        $this->assertSame(\spl_object_id($componentA->getService()), \spl_object_id($componentB->getService()));
        $this->assertSame('A', $componentA->propA);
        $this->assertSame('B', $componentA->getPropB());
        $this->assertSame('C', $componentB->propA);
        $this->assertSame('D', $componentB->getPropB());
    }

    /**
     * @test
     */
    public function non_auto_configured_created_components_are_not_shared(): void
    {
        self::bootKernel();

        /** @var ComponentFactory $factory */
        $factory = self::$container->get(ComponentFactory::class);

        /** @var ComponentB $componentA */
        $componentA = $factory->create('component_b');

        /** @var ComponentB $componentB */
        $componentB = $factory->create('component_b');

        $this->assertNotSame(\spl_object_id($componentA), \spl_object_id($componentB));
    }

    /**
     * @test
     */
    public function short_name_cannot_be_different_than_component_name(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Component "Symfony\UX\TwigComponent\Tests\Fixture\Component\ComponentB" is already registered as "component_b", components cannot be registered more than once.');

        self::bootKernel(['environment' => 'multiple_component_b']);
    }

    /**
     * @test
     */
    public function can_get_service_id(): void
    {
        self::bootKernel();

        /** @var ComponentFactory $factory */
        $factory = self::$container->get(ComponentFactory::class);

        $this->assertSame(ComponentA::class, $factory->serviceIdFor('component_a'));
        $this->assertSame('component_b', $factory->serviceIdFor('component_b'));
    }

    /**
     * @test
     */
    public function can_get_unmounted_component(): void
    {
        self::bootKernel();

        /** @var ComponentFactory $factory */
        $factory = self::$container->get(ComponentFactory::class);

        /** @var ComponentA $component */
        $component = $factory->get('component_a');

        $this->assertNull($component->propA);
        $this->assertNull($component->getPropB());
    }

    /**
     * @test
     */
    public function mount_can_have_optional_parameters(): void
    {
        self::bootKernel();

        /** @var ComponentFactory $factory */
        $factory = self::$container->get(ComponentFactory::class);

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

    /**
     * @test
     */
    public function exception_thrown_if_required_mount_parameter_is_missing_from_passed_data(): void
    {
        self::bootKernel();

        /** @var ComponentFactory $factory */
        $factory = self::$container->get(ComponentFactory::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Symfony\UX\TwigComponent\Tests\Fixture\Component\ComponentC::mount() has a required $propA parameter. Make sure this is passed or make give a default value.');

        $factory->create('component_c');
    }

    /**
     * @test
     */
    public function exception_thrown_if_unable_to_write_passed_data_to_property(): void
    {
        self::bootKernel();

        /** @var ComponentFactory $factory */
        $factory = self::$container->get(ComponentFactory::class);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to write "service" to component "Symfony\UX\TwigComponent\Tests\Fixture\Component\ComponentA". Make sure this is a writable property or create a mount() with a $service argument.');

        $factory->create('component_a', ['propB' => 'B', 'service' => 'invalid']);
    }
}
