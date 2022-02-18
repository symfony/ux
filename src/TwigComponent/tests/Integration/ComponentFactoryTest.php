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
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\ComponentA;
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\ComponentB;
use Symfony\UX\TwigComponent\Tests\Fixtures\Component\ComponentC;

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

    public function testExceptionThrownIfUnableToWritePassedDataToPropertyAndIsNotScalar(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to use "service" (stdClass) as an attribute. Attributes must be scalar or null.');

        $this->createComponent('component_a', ['propB' => 'B', 'service' => new \stdClass()]);
    }

    public function testTwigComponentServiceTagMustHaveKey(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('"twig.component" tag for service "missing_key" requires a "key" attribute.');

        self::bootKernel(['environment' => 'missing_key']);
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
        $this->expectExceptionMessage('Unknown component "invalid". The registered components are: component_a');

        $this->factory()->metadataFor('invalid');
    }

    public function testCannotGetInvalidComponent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown component "invalid". The registered components are: component_a');

        $this->factory()->get('invalid');
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
