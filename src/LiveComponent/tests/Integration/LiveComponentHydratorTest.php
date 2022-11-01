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
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component1;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component2;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\Component3;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\ComponentWithArrayProp;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Embeddable2;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Money;
use Symfony\UX\LiveComponent\Tests\Fixtures\Dto\Temperature;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Embeddable1;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity1;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\Entity2;
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\EmptyStringEnum;
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\IntEnum;
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\StringEnum;
use Symfony\UX\LiveComponent\Tests\Fixtures\Enum\ZeroIntEnum;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

use function Zenstruck\Foundry\create;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LiveComponentHydratorTest extends KernelTestCase
{
    use Factories;
    use LiveComponentTestHelper;
    use ResetDatabase;

    public function testCanDehydrateAndHydrateLiveComponent(): void
    {
        $mounted = $this->mountComponent('component1', [
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

        $dehydrated = $this->dehydrateComponent($mounted);
        $props = $dehydrated->getProps();
        $data = $dehydrated->getData();

        $this->assertSame($prop1->id, $props['prop1']);
        $this->assertSame($prop2->format('c'), $props['prop2']);
        $this->assertSame($prop3, $data['prop3']);
        $this->assertArrayHasKey('_checksum', $props);
        $this->assertArrayNotHasKey('prop4', $props);
        $this->assertArrayNotHasKey('prop4', $data);

        $component = $this->getComponent('component1');

        $this->hydrateComponent($component, $dehydrated->all(), $mounted->getName());

        $this->assertSame($prop1->id, $component->prop1->id);
        $this->assertSame($prop2->format('c'), $component->prop2->format('c'));
        $this->assertSame($prop3, $component->prop3);
        $this->assertNull($component->prop4);
    }

    public function testCanModifyWritableProps(): void
    {
        $mounted = $this->mountComponent('component1', [
            'prop1' => create(Entity1::class)->object(),
            'prop2' => new \DateTime('2021-03-05 9:23'),
            'prop3' => 'value3',
        ]);

        $dehydrated = $this->dehydrateComponent($mounted)->all();
        $dehydrated['prop3'] = 'new value';

        $component = $this->getComponent('component1');

        $this->hydrateComponent($component, $dehydrated, $mounted->getName());

        $this->assertSame('new value', $component->prop3);
    }

    public function testCannotModifyReadonlyProps(): void
    {
        $mounted = $this->mountComponent('component1', [
            'prop1' => create(Entity1::class)->object(),
            'prop2' => new \DateTime('2021-03-05 9:23'),
            'prop3' => 'value3',
        ]);

        $dehydrated = $this->dehydrateComponent($mounted)->all();
        $dehydrated['prop2'] = (new \DateTime())->format('c');

        $component = $this->getComponent('component1');

        $this->expectException(\RuntimeException::class);
        $this->hydrateComponent($component, $dehydrated, $mounted->getName());
    }

    public function testHydrationFailsIfChecksumMissing(): void
    {
        $component = $this->getComponent('component1');

        $this->expectException(\RuntimeException::class);

        $this->hydrateComponent($component, [], 'component1');
    }

    public function testHydrationFailsOnChecksumMismatch(): void
    {
        $component = $this->getComponent('component1');

        $this->expectException(\RuntimeException::class);

        $this->hydrateComponent($component, ['_checksum' => 'invalid'], 'component1');
    }

    public function testPreDehydrateAndPostHydrateHooksCalled(): void
    {
        $mounted = $this->mountComponent('component2');

        /** @var Component2 $component */
        $component = $mounted->getComponent();

        $this->assertFalse($component->preDehydrateCalled);
        $this->assertFalse($component->postHydrateCalled);

        $data = $this->dehydrateComponent($mounted)->all();

        $this->assertTrue($component->preDehydrateCalled);
        $this->assertFalse($component->postHydrateCalled);

        /** @var Component2 $component */
        $component = $this->getComponent('component2');

        $this->assertFalse($component->preDehydrateCalled);
        $this->assertFalse($component->postHydrateCalled);

        $this->hydrateComponent($component, $data, $mounted->getName());

        $this->assertFalse($component->preDehydrateCalled);
        $this->assertTrue($component->postHydrateCalled);
    }

    public function testDeletingEntityBetweenDehydrationAndHydrationSetsItToNull(): void
    {
        $entity = create(Entity1::class);

        $mounted = $this->mountComponent('component1', [
            'prop1' => $entity->object(),
            'prop2' => new \DateTime('2021-03-05 9:23'),
        ]);

        /** @var Component1 $component */
        $component = $mounted->getComponent();

        $this->assertSame($entity->id, $component->prop1->id);

        $data = $this->dehydrateComponent($mounted)->all();

        $this->assertSame($entity->id, $data['prop1']);

        $entity->remove();

        /** @var Component1 $component */
        $component = $this->getComponent('component1');

        $mounted = $this->hydrateComponent($component, $data, $mounted->getName());

        $this->assertNull($component->prop1);

        $data = $this->dehydrateComponent($mounted)->all();

        $this->assertNull($data['prop1']);
    }

    public function testCorrectlyUsesCustomFrontendNameInDehydrateAndHydrate(): void
    {
        $mounted = $this->mountComponent('component3', ['prop1' => 'value1', 'prop2' => 'value2']);
        $dehydrated = $this->dehydrateComponent($mounted)->all();

        $this->assertArrayNotHasKey('prop1', $dehydrated);
        $this->assertArrayNotHasKey('prop2', $dehydrated);
        $this->assertArrayHasKey('myProp1', $dehydrated);
        $this->assertArrayHasKey('myProp2', $dehydrated);
        $this->assertSame('value1', $dehydrated['myProp1']);
        $this->assertSame('value2', $dehydrated['myProp2']);

        /** @var Component3 $component */
        $component = $this->getComponent('component3');

        $this->hydrateComponent($component, $dehydrated, $mounted->getName());

        $this->assertSame('value1', $component->prop1);
        $this->assertSame('value2', $component->prop2);
    }

    public function testCanDehydrateAndHydrateArrays(): void
    {
        $array = ['some', 'array'];
        $mounted = $this->mountComponent('component_with_array_prop', ['prop' => $array]);

        /** @var ComponentWithArrayProp $component */
        $component = $mounted->getComponent();

        $this->assertSame($array, $component->prop);

        $dehydrated = $this->dehydrateComponent($mounted)->all();

        $this->assertArrayHasKey('prop', $dehydrated);
        $this->assertSame($array, $dehydrated['prop']);

        /** @var ComponentWithArrayProp $component */
        $component = $this->getComponent('component_with_array_prop');

        $this->assertSame([], $component->prop);

        $this->hydrateComponent($component, $dehydrated, 'component_with_array_prop');

        $this->assertSame($array, $component->prop);
    }

    public function testCanDehydrateAndHydrateEmptyArrays(): void
    {
        $mounted = $this->mountComponent('component_with_array_prop');

        /** @var ComponentWithArrayProp $component */
        $component = $mounted->getComponent();

        $this->assertSame([], $component->prop);

        $dehydrated = $this->dehydrateComponent($mounted)->all();

        $this->assertArrayHasKey('prop', $dehydrated);
        $this->assertSame([], $dehydrated['prop']);

        /** @var ComponentWithArrayProp $component */
        $component = $this->getComponent('component_with_array_prop');

        $this->assertSame([], $component->prop);

        $this->hydrateComponent($component, $dehydrated, 'component_with_array_prop');

        $this->assertSame([], $component->prop);
    }

    public function testCanDehydrateAndHydrateComponentsWithAttributes(): void
    {
        $mounted = $this->mountComponent('with_attributes', $attributes = ['class' => 'foo', 'value' => null]);

        $this->assertSame($attributes, $mounted->getAttributes()->all());

        $dehydrated = $this->dehydrateComponent($mounted)->all();

        $this->assertArrayHasKey('_attributes', $dehydrated);
        $this->assertSame($attributes, $dehydrated['_attributes']);

        $mounted = $this->hydrateComponent($this->getComponent('with_attributes'), $dehydrated, $mounted->getName());

        $this->assertSame($attributes, $mounted->getAttributes()->all());
    }

    public function testCanDehydrateAndHydrateComponentsWithEmptyAttributes(): void
    {
        $mounted = $this->mountComponent('with_attributes');

        $this->assertSame([], $mounted->getAttributes()->all());

        $dehydrated = $this->dehydrateComponent($mounted)->all();

        $this->assertArrayNotHasKey('_attributes', $dehydrated);

        $mounted = $this->hydrateComponent($this->getComponent('with_attributes'), $dehydrated, $mounted->getName());

        $this->assertSame([], $mounted->getAttributes()->all());
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testCanHydrateEnums(): void
    {
        $mounted = $this->mountComponent('with_enum');

        $dehydrated = $this->dehydrateComponent($mounted)->all();

        $this->assertNull($dehydrated['int']);
        $this->assertNull($dehydrated['string']);

        $mounted = $this->hydrateComponent($this->getComponent('with_enum'), $dehydrated, $mounted->getName());

        $this->assertNull($mounted->getComponent()->int);
        $this->assertNull($mounted->getComponent()->string);

        $dehydrated['int'] = IntEnum::LOW->value;
        $dehydrated['string'] = StringEnum::PENDING->value;

        $mounted = $this->hydrateComponent($this->getComponent('with_enum'), $dehydrated, $mounted->getName());

        $this->assertSame(IntEnum::LOW, $mounted->getComponent()->int);
        $this->assertSame(StringEnum::PENDING, $mounted->getComponent()->string);

        $dehydrated['int'] = null;
        $dehydrated['string'] = null;

        $mounted = $this->hydrateComponent($this->getComponent('with_enum'), $dehydrated, $mounted->getName());

        $this->assertNull($mounted->getComponent()->int);
        $this->assertNull($mounted->getComponent()->string);

        $dehydrated['int'] = '';
        $dehydrated['string'] = '';

        $mounted = $this->hydrateComponent($this->getComponent('with_enum'), $dehydrated, $mounted->getName());

        $this->assertNull($mounted->getComponent()->int);
        $this->assertNull($mounted->getComponent()->string);

        $dehydrated['zeroInt'] = 0;
        $dehydrated['emptyString'] = '';

        $mounted = $this->hydrateComponent($this->getComponent('with_enum'), $dehydrated, $mounted->getName());

        $this->assertSame(ZeroIntEnum::ZERO, $mounted->getComponent()->zeroInt);
        $this->assertSame(EmptyStringEnum::EMPTY, $mounted->getComponent()->emptyString);

        $dehydrated['zeroInt'] = '0';

        $mounted = $this->hydrateComponent($this->getComponent('with_enum'), $dehydrated, $mounted->getName());

        $this->assertSame(ZeroIntEnum::ZERO, $mounted->getComponent()->zeroInt);
    }

    public function testComponentWithNormalizableObjects(): void
    {
        $mounted = $this->mountComponent('with_objects', [
            'money' => new Money(500, 'CAD'),
            'temperature' => new Temperature(30, 'C'),
            'entity1' => $entity1 = create(Entity1::class)->object(),
            'entity2' => $entity2 = create(Entity2::class, ['embedded1' => new Embeddable1('bar'), 'embedded2' => new Embeddable2('baz')])->object(),
            'embeddable1' => new Embeddable1('foo'),
            'embeddable2' => new Embeddable2('qux'),
        ]);

        $dehydrated = $this->dehydrateComponent($mounted)->all();

        $this->assertSame('500|CAD', $dehydrated['money']);
        $this->assertSame(['degrees' => 30, 'uom' => 'C'], $dehydrated['temperature']);
        $this->assertSame($entity1->id, $dehydrated['entity1']);
        $this->assertSame('entity2:'.$entity2->id, $dehydrated['entity2']);
        $this->assertSame(['name' => 'foo'], $dehydrated['embeddable1']);
        $this->assertSame(['name' => 'qux'], $dehydrated['embeddable2']);

        $mounted = $this->hydrateComponent($this->getComponent('with_objects'), $dehydrated, $mounted->getName());
        $component = $mounted->getComponent();

        $this->assertSame(500, $component->money->amount);
        $this->assertSame('CAD', $component->money->currency);
        $this->assertSame(30, $component->temperature->degrees);
        $this->assertSame('C', $component->temperature->uom);
        $this->assertSame($entity1->id, $component->entity1->id);
        $this->assertSame($entity2->id, $component->entity2->id);
        $this->assertSame('bar', $component->entity2->embedded1->name);
        $this->assertSame('baz', $component->entity2->embedded2->name);
        $this->assertSame('foo', $component->embeddable1->name);
        $this->assertSame('qux', $component->embeddable2->name);
    }
}
