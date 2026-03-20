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
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Metadata\LegacyLivePropMetadata;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Metadata\LivePropMetadata;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\LivePropInheritanceChild;
use Symfony\UX\LiveComponent\Tests\Fixtures\Component\LivePropInheritanceParent;

/**
 * Regression tests for the PHP reflection gap that caused #[LiveProp] attributes
 * declared in traits used by a *parent* class to be registered without their
 * fieldName callable on a *child* component, and #[LiveAction]/#[LiveListener]
 * methods from parent classes to be undiscoverable on child components.
 *
 * @group trait-inheritance
 */
final class LivePropInheritanceTest extends KernelTestCase
{
    /**
     * Sanity check: the parent component itself must have the LiveProp with
     * the correct fieldName callable.
     */
    public function testParentComponentHasLivePropWithFieldName()
    {
        self::bootKernel();

        $prop = $this->findPropMetadata(LivePropInheritanceParent::class, 'formValues');

        $this->assertNotNull($prop, 'formValues LiveProp must be registered on the parent component.');

        $component = new LivePropInheritanceParent();
        $this->assertSame(
            'live_prop_inheritance_form',
            $prop->calculateFieldName($component, 'formValues'),
            'fieldName callable must resolve correctly on the parent component.'
        );
    }

    /**
     * Regression test for Bug #2: the child component must have the same
     * LiveProp with the correct fieldName callable, even though it does not
     * redeclare the trait.
     *
     * Before the fix the LiveProp was either not registered at all for the child,
     * or registered without the fieldName, causing the frontend key mismatch.
     */
    public function testChildComponentInheritsLivePropWithFieldName()
    {
        self::bootKernel();

        $prop = $this->findPropMetadata(LivePropInheritanceChild::class, 'formValues');

        $this->assertNotNull(
            $prop,
            'formValues LiveProp must be registered on the child component.'
        );

        $component = new LivePropInheritanceChild();
        $this->assertSame(
            'live_prop_inheritance_form',
            $prop->calculateFieldName($component, 'formValues'),
            'The fieldName callable must be preserved on the child component when '.
            '#[LiveProp] is declared in a trait used by the parent class.'
        );
    }

    /**
     * Sanity check: the save() action must be allowed on the parent component.
     */
    public function testParentComponentHasSaveAction()
    {
        self::bootKernel();

        $this->assertTrue(
            AsLiveComponent::isActionAllowed(LivePropInheritanceParent::class, 'save'),
            'save() must be a recognised LiveAction on the parent component.'
        );
    }

    /**
     * Regression test for Bug #3: the save() action declared with #[LiveAction]
     * on the parent class must also be allowed on the child component.
     */
    public function testChildComponentInheritsLiveAction()
    {
        self::bootKernel();

        $this->assertTrue(
            AsLiveComponent::isActionAllowed(LivePropInheritanceChild::class, 'save'),
            'save() must be a recognised LiveAction on the child component when it is '.
            'declared with #[LiveAction] on the parent class.'
        );
    }

    /**
     * Regression test for Bug #3: the save() listener declared with
     * #[LiveListener("save")] on the parent class must appear in the child's
     * live listeners.
     */
    public function testChildComponentInheritsLiveListener()
    {
        self::bootKernel();

        $child = new LivePropInheritanceChild();
        $listeners = AsLiveComponent::liveListeners($child);

        $this->assertContains(
            ['action' => 'save', 'event' => 'save'],
            $listeners,
            'The save listener must be registered on the child component when '.
            '#[LiveListener] is declared on the parent class method.'
        );
    }

    /**
     * @param class-string $componentClass
     */
    private function findPropMetadata(string $componentClass, string $propName): LivePropMetadata|LegacyLivePropMetadata|null
    {
        /** @var LiveComponentMetadataFactory $factory */
        $factory = self::getContainer()->get('ux.live_component.metadata_factory');

        $props = $factory->createPropMetadatas(new \ReflectionClass($componentClass));

        foreach ($props as $prop) {
            if ($prop->getName() === $propName) {
                return $prop;
            }
        }

        return null;
    }
}
