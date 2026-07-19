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
    public function testConvertsExamplesBlockAndDropsScaffolding()
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
            TWIG);

        // Twig scaffolding stripped, examples block becomes a section.
        $this->assertStringNotContainsString('{%', $doc);
        $this->assertStringNotContainsString('toolkit_code_', $doc);
        $this->assertStringContainsString("## Examples\n", $doc);
        $this->assertStringContainsString('### Basic', $doc);
        $this->assertStringContainsString('A basic alert.', $doc);

        // toolkit_code_example(...) -> ::: example with JSON options.
        $this->assertStringContainsString('::: example Basic', $doc);
        $this->assertStringContainsString('::: example RTL {"height": "450px", "collapseClass": true}', $doc);

        // The demo block is dropped (the recipe template regenerates the Demo).
        $this->assertStringNotContainsString('example Demo', $doc);
    }

    public function testConvertsUsageBlock()
    {
        $doc = MdTwigConverter::convert(<<<'TWIG'
            {% block usage %}
            {{ toolkit_code_usage(kit_id.value, component.name) }}
            {% endblock %}
            TWIG);

        $this->assertStringContainsString('## Usage', $doc);
        $this->assertStringContainsString('::: example Usage', $doc);
    }
}
