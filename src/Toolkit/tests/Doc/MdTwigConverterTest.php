<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Doc;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Doc\MdTwigConverter;

final class MdTwigConverterTest extends TestCase
{
    public function testProducesTheFullDocumentBodyWithDirectives()
    {
        $doc = MdTwigConverter::convert(<<<'TWIG'
            {% extends 'toolkit/docs/_base_component.md.twig' %}

            {% block demo %}
            {{ toolkit_code_demo(kit_id.value, component.name, {height: '300px'}) }}
            {% endblock %}

            {% block examples %}
            ### Basic

            A basic alert.

            {{ toolkit_code_example(kit_id.value, component.name, 'Basic') }}

            ### RTL

            {{ toolkit_code_example(kit_id.value, component.name, 'RTL', {height: '450px', collapseClass: true}) }}
            {% endblock %}
            TWIG, 'Alert', 'Displays a callout for user attention.');

        $this->assertStringNotContainsString('{%', $doc);
        $this->assertStringNotContainsString('toolkit_code_', $doc);

        // The README.md is self-contained: it opens with the recipe title and description.
        $this->assertStringStartsWith("# Alert\n\nDisplays a callout for user attention.", $doc);
        // The demo, installation and API reference are laid out around the authored examples.
        $this->assertStringContainsString('::: example Demo {"height": "300px"}', $doc);
        $this->assertStringContainsString("## Installation\n\n::: installation", $doc);
        $this->assertStringContainsString("## API Reference\n\n::: api-reference", $doc);

        // The examples block becomes an "Examples" section made of `::: example` directives.
        $this->assertStringContainsString('## Examples', $doc);
        $this->assertStringContainsString('### Basic', $doc);
        $this->assertStringContainsString('A basic alert.', $doc);
        $this->assertStringContainsString('::: example Basic', $doc);
        $this->assertStringContainsString('::: example RTL {"height": "450px", "collapseClass": true}', $doc);
    }

    public function testConvertsUsageBlock()
    {
        $doc = MdTwigConverter::convert(<<<'TWIG'
            {% block usage %}
            {{ toolkit_code_usage(kit_id.value, component.name) }}
            {% endblock %}
            TWIG, 'Button', 'A button.');

        $this->assertStringContainsString("## Usage\n\n::: example Usage", $doc);
        // Installation and API reference are always laid out.
        $this->assertStringContainsString('::: installation', $doc);
        $this->assertStringContainsString('::: api-reference', $doc);
    }
}
