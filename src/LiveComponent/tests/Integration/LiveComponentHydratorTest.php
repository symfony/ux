<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component1;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component2;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component3;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;
use Symfony\UX\TwigComponent\ComponentAttributes;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\MountedComponent;
use function Zenstruck\Foundry\create;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LiveComponentHydratorTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testCanDehydrateAndHydrateLiveComponent(): void
    {
        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::getContainer()->get('ux.live_component.component_hydrator');

        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $mounted = $factory->create('component1', [
            'prop1' => $prop1 = create(Entity1::class)->object(),
            'prop2' => $prop2 = new \DateTime('2021-03-05 9:23'),
            'prop3' => $prop3 = 'value3',
            'prop4' => $prop4 = 'value4',
        ]);

        /** @var Component1 $component */
        $component = $mounted->getComponent();

        $this->assertSame($prop1, $component->prop1);
        $this->assertSame($prop2, $component->prop2);
        $this->assertSame($prop3, $component->prop3);
        $this->assertSame($prop4, $component->prop4);

        $dehydrated = $hydrator->dehydrate($mounted);

        $this->assertSame($prop1->id, $dehydrated['prop1']);
        $this->assertSame($prop2->format('c'), $dehydrated['prop2']);
        $this->assertSame($prop3, $dehydrated['prop3']);
        $this->assertArrayHasKey('_checksum', $dehydrated);
        $this->assertArrayNotHasKey('prop4', $dehydrated);

        $component = $factory->get('component1');

        $hydrator->hydrate($component, $dehydrated, $mounted->getName());

        $this->assertSame($prop1->id, $component->prop1->id);
        $this->assertSame($prop2->format('c'), $component->prop2->format('c'));
        $this->assertSame($prop3, $component->prop3);
        $this->assertNull($component->prop4);
    }

    public function testCanModifyWritableProps(): void
    {
        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::getContainer()->get('ux.live_component.component_hydrator');

        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $mounted = $factory->create('component1', [
            'prop1' => create(Entity1::class)->object(),
            'prop2' => new \DateTime('2021-03-05 9:23'),
            'prop3' => 'value3',
        ]);

        $dehydrated = $hydrator->dehydrate($mounted);
        $dehydrated['prop3'] = 'new value';

        $component = $factory->get('component1');

        $hydrator->hydrate($component, $dehydrated, $mounted->getName());

        $this->assertSame('new value', $component->prop3);
    }

    public function testCannotModifyReadonlyProps(): void
    {
        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::getContainer()->get('ux.live_component.component_hydrator');

        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $mounted = $factory->create('component1', [
            'prop1' => create(Entity1::class)->object(),
            'prop2' => new \DateTime('2021-03-05 9:23'),
            'prop3' => 'value3',
        ]);

        $dehydrated = $hydrator->dehydrate($mounted);
        $dehydrated['prop2'] = (new \DateTime())->format('c');

        $component = $factory->get('component1');

        $this->expectException(\RuntimeException::class);
        $hydrator->hydrate($component, $dehydrated, $mounted->getName());
    }

    public function testHydrationFailsIfChecksumMissing(): void
    {
        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::getContainer()->get('ux.live_component.component_hydrator');

        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $this->expectException(\RuntimeException::class);
        $hydrator->hydrate($factory->get('component1'), [], 'component1');
    }

    public function testHydrationFailsOnChecksumMismatch(): void
    {
        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::getContainer()->get('ux.live_component.component_hydrator');

        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $this->expectException(\RuntimeException::class);
        $hydrator->hydrate($factory->get('component1'), ['_checksum' => 'invalid'], 'component1');
    }

    public function testPreDehydrateAndPostHydrateHooksCalled(): void
    {
        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::getContainer()->get('ux.live_component.component_hydrator');

        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $mounted = $factory->create('component2');

        /** @var Component2 $component */
        $component = $mounted->getComponent();

        $this->assertFalse($component->preDehydrateCalled);
        $this->assertFalse($component->postHydrateCalled);

        $data = $hydrator->dehydrate($mounted);

        $this->assertTrue($component->preDehydrateCalled);
        $this->assertFalse($component->postHydrateCalled);

        /** @var Component2 $component */
        $component = $factory->get('component2');

        $this->assertFalse($component->preDehydrateCalled);
        $this->assertFalse($component->postHydrateCalled);

        $hydrator->hydrate($component, $data, $mounted->getName());

        $this->assertFalse($component->preDehydrateCalled);
        $this->assertTrue($component->postHydrateCalled);
    }

    public function testDeletingEntityBetweenDehydrationAndHydrationSetsItToNull(): void
    {
        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::getContainer()->get('ux.live_component.component_hydrator');

        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $entity = create(Entity1::class);

        $mounted = $factory->create('component1', [
            'prop1' => $entity->object(),
            'prop2' => new \DateTime('2021-03-05 9:23'),
        ]);

        /** @var Component1 $component */
        $component = $mounted->getComponent();

        $this->assertSame($entity->id, $component->prop1->id);

        $data = $hydrator->dehydrate($mounted);

        $this->assertSame($entity->id, $data['prop1']);

        $entity->remove();

        /** @var Component1 $component */
        $component = $factory->get('component1');

        $mounted = $hydrator->hydrate($component, $data, $mounted->getName());

        $this->assertNull($component->prop1);

        $data = $hydrator->dehydrate($mounted);

        $this->assertNull($data['prop1']);
    }

    public function testCorrectlyUsesCustomFrontendNameInDehydrateAndHydrate(): void
    {
        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::getContainer()->get('ux.live_component.component_hydrator');

        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $mounted = $factory->create('component3', ['prop1' => 'value1', 'prop2' => 'value2']);

        /** @var Component3 $component */
        $component = $mounted->getComponent();

        $dehydrated = $hydrator->dehydrate($mounted);

        $this->assertArrayNotHasKey('prop1', $dehydrated);
        $this->assertArrayNotHasKey('prop2', $dehydrated);
        $this->assertArrayHasKey('myProp1', $dehydrated);
        $this->assertArrayHasKey('myProp2', $dehydrated);
        $this->assertSame('value1', $dehydrated['myProp1']);
        $this->assertSame('value2', $dehydrated['myProp2']);

        /** @var Component3 $component */
        $component = $factory->get('component3');

        $hydrator->hydrate($component, $dehydrated, $mounted->getName());

        $this->assertSame('value1', $component->prop1);
        $this->assertSame('value2', $component->prop2);
    }

    public function testCanDehydrateAndHydrateArrays(): void
    {
        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::getContainer()->get('ux.live_component.component_hydrator');

        $component = new class() {
            #[LiveProp]
            public array $prop;
        };

        $instance = clone $component;
        $instance->prop = ['some', 'array'];

        $dehydrated = $hydrator->dehydrate(new MountedComponent('my_component', $instance, new ComponentAttributes([])));

        $this->assertArrayHasKey('prop', $dehydrated);
        $this->assertSame($instance->prop, $dehydrated['prop']);

        $this->assertFalse(isset($component->prop));

        $hydrator->hydrate($component, $dehydrated, 'my_component');

        $this->assertSame($instance->prop, $component->prop);
    }

    public function testCanDehydrateAndHydrateComponentsWithAttributes(): void
    {
        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::getContainer()->get('ux.live_component.component_hydrator');

        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $mounted = $factory->create('with_attributes', $attributes = ['class' => 'foo', 'value' => null]);

        $this->assertSame($attributes, $mounted->getAttributes()->all());

        $dehydrated = $hydrator->dehydrate($mounted);

        $this->assertArrayHasKey('_attributes', $dehydrated);
        $this->assertSame($attributes, $dehydrated['_attributes']);

        $mounted = $hydrator->hydrate($factory->get('with_attributes'), $dehydrated, $mounted->getName());

        $this->assertSame($attributes, $mounted->getAttributes()->all());
    }

    public function testCanDehydrateAndHydrateComponentsWithEmptyAttributes(): void
    {
        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::getContainer()->get('ux.live_component.component_hydrator');

        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        $mounted = $factory->create('with_attributes');

        $this->assertSame([], $mounted->getAttributes()->all());

        $dehydrated = $hydrator->dehydrate($mounted);

        $this->assertArrayNotHasKey('_attributes', $dehydrated);

        $mounted = $hydrator->hydrate($factory->get('with_attributes'), $dehydrated, $mounted->getName());

        $this->assertSame([], $mounted->getAttributes()->all());
    }
}
