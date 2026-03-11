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
use Twig\Environment;

/**
 * Regression tests for the PHP reflection gap that caused #[ExposeInTemplate]
 * properties declared in traits used by a *parent* class to be invisible when
 * rendering a *child* component that extends that parent without redeclaring
 * the trait.
 *
 * Root cause: ReflectionClass::getProperties() on the child class does not
 * return private properties from traits used in ancestor classes.
 *
 * @group trait-inheritance
 */
final class ExposeInTemplateTraitInheritanceTest extends KernelTestCase
{
    /**
     * Sanity check: the parent component itself must expose the trait property.
     * This works in all versions because the parent directly uses the trait.
     */
    public function testParentComponentExposesTraitProperty()
    {
        self::bootKernel();

        $output = $this->renderComponent('WithExposedTraitParent');

        $this->assertStringContainsString('ExposedFromTrait: trait-value', $output);
    }

    /**
     * Regression test for Bug #1: the child component must also expose the
     * private trait property inherited through the parent class.
     *
     * Before the fix, this throws:
     *   Variable "exposed_from_trait" does not exist in ...WithExposedTraitChild.html.twig
     */
    public function testChildComponentExposesInheritedTraitProperty()
    {
        self::bootKernel();

        $output = $this->renderComponent('WithExposedTraitChild');

        $this->assertStringContainsString(
            'ExposedFromTrait: trait-value',
            $output,
            'A child component must expose private #[ExposeInTemplate] properties '.
            'from traits used by its parent class.'
        );
    }

    private function renderComponent(string $name, array $data = []): string
    {
        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);

        return $twig->render('render_component.html.twig', [
            'name' => $name,
            'data' => $data,
        ]);
    }
}
