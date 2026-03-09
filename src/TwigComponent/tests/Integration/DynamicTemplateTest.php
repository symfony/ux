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
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

class DynamicTemplateTest extends KernelTestCase
{
    use InteractsWithTwigComponents;

    public function testTemplateResolvedAutomaticallyWhenNotProvided()
    {
        // When no template is explicitly configured, the default resolution mechanism should be applied.
        $rendered = (string) $this->renderTwigComponent('default_template_component');

        $this->assertStringContainsString('Default Template Works!', $rendered);
    }

    public function testTemplateResolvedFromString()
    {
        // Ensures that a template defined as a string is properly resolved.
        $rendered = (string) $this->renderTwigComponent('string_template_component');

        $this->assertStringContainsString('String Template Works!', $rendered);
    }

    public function testTemplateChangesBasedOnComponentProps()
    {
        // 1. Default Type props
        $html = $this->renderTwigComponent('method_template_component', ['type' => 'default']);
        $this->assertStringContainsString('Default', $html);

        // 2. Custom Type props
        $html = $this->renderTwigComponent('method_template_component', ['type' => 'custom']);
        $this->assertStringContainsString('Custom', $html);
    }

    public function testTemplateFromMethodThrowsExceptionWhenMethodIsMissing()
    {
        // Using FromMethod with a non-existing or non-callable method must throw a LogicException.
        $this->expectException(\Twig\Error\RuntimeError::class);
        $this->expectExceptionMessage('does not exist or is not callable');

        $this->renderTwigComponent('missing_method_component');
    }
}
