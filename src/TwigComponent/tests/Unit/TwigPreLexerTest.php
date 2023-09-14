<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\UX\TwigComponent\Twig\TwigPreLexer;

final class TwigPreLexerTest extends TestCase
{
    /**
     * @dataProvider getLexTests
     */
    public function testPreLex(string $input, string $expectedOutput): void
    {
        $lexer = new TwigPreLexer();
        $this->assertSame($expectedOutput, $lexer->preLexComponents($input));
    }

    public static function getLexTests(): iterable
    {
        yield 'simple_component' => [
            '<twig:foo />',
            '{{ component(\'foo\') }}',
        ];

        yield 'component_with_attributes' => [
            '<twig:foo bar="baz" with_quotes="It\'s with quotes" />',
            "{{ component('foo', { bar: 'baz', with_quotes: 'It\'s with quotes' }) }}",
        ];

        yield 'component_with_dynamic_attributes' => [
            '<twig:foo dynamic="{{ dynamicVar }}" :otherDynamic="anotherVar" />',
            '{{ component(\'foo\', { dynamic: (dynamicVar), otherDynamic: anotherVar }) }}',
        ];

        yield 'component_with_closing_tag' => [
            '<twig:foo></twig:foo>',
            '{% component \'foo\' %}{% endcomponent %}',
        ];

        yield 'component_with_block' => [
            '<twig:foo><twig:block name="foo_block">Foo</twig:block></twig:foo>',
            '{% component \'foo\' %}{% block foo_block %}Foo{% endblock %}{% endcomponent %}',
        ];

        yield 'component_with_traditional_block' => [
            '<twig:foo>{% block foo_block %}Foo{% endblock %}</twig:foo>',
            '{% component \'foo\' %}{% block foo_block %}Foo{% endblock %}{% endcomponent %}',
        ];

        yield 'traditional_blocks_around_component_do_not_confuse' => [
            'Hello {% block foo_block %}Foo{% endblock %}<twig:foo />{% block bar_block %}Bar{% endblock %}',
            'Hello {% block foo_block %}Foo{% endblock %}{{ component(\'foo\') }}{% block bar_block %}Bar{% endblock %}',
        ];

        yield 'component_with_component_inside_block' => [
            '<twig:foo><twig:block name="foo_block"><twig:bar /></twig:block></twig:foo>',
            '{% component \'foo\' %}{% block foo_block %}{{ component(\'bar\') }}{% endblock %}{% endcomponent %}',
        ];

        yield 'component_with_embedded_component_inside_block' => [
            '<twig:foo><twig:block name="foo_block"><twig:bar><twig:baz /></twig:bar></twig:block></twig:foo>',
            '{% component \'foo\' %}{% block foo_block %}{% component \'bar\' %}{% block content %}{{ component(\'baz\') }}{% endblock %}{% endcomponent %}{% endblock %}{% endcomponent %}',
        ];

        yield 'component_with_embedded_component' => [
            '<twig:foo>foo_content<twig:bar><twig:baz /></twig:bar></twig:foo>',
            '{% component \'foo\' %}{% block content %}foo_content{% component \'bar\' %}{% block content %}{{ component(\'baz\') }}{% endblock %}{% endcomponent %}{% endblock %}{% endcomponent %}',
        ];

        yield 'attribute_with_no_value' => [
            '<twig:foo bar />',
            '{{ component(\'foo\', { bar: true }) }}',
        ];

        yield 'attribute_with_no_value_and_no_attributes' => [
            '<twig:foo/>',
            '{{ component(\'foo\') }}',
        ];

        yield 'component_with_default_block_content' => [
            '<twig:foo>Foo</twig:foo>',
            '{% component \'foo\' %}{% block content %}Foo{% endblock %}{% endcomponent %}',
        ];

        yield 'component_with_default_block_that_holds_a_component_and_multi_blocks' => [
            '<twig:foo>Foo <twig:bar /><twig:block name="other_block">Other block</twig:block></twig:foo>',
            '{% component \'foo\' %}{% block content %}Foo {{ component(\'bar\') }}{% endblock %}{% block other_block %}Other block{% endblock %}{% endcomponent %}',
        ];
        yield 'component_with_character_:_on_his_name' => [
            '<twig:foo:bar></twig:foo:bar>',
            '{% component \'foo:bar\' %}{% endcomponent %}',
        ];
        yield 'component_with_character_@_on_his_name' => [
            '<twig:@foo></twig:@foo>',
            '{% component \'@foo\' %}{% endcomponent %}',
        ];
        yield 'component_with_character_-_on_his_name' => [
            '<twig:foo-bar></twig:foo-bar>',
            '{% component \'foo-bar\' %}{% endcomponent %}',
        ];
        yield 'component_with_character_._on_his_name' => [
            '<twig:foo.bar></twig:foo.bar>',
            '{% component \'foo.bar\' %}{% endcomponent %}',
        ];
        yield 'nested_component_2_levels' => [
            '<twig:foo><twig:block name="child"><twig:bar><twig:block name="message">Hello World!</twig:block></twig:bar></twig:block></twig:foo>',
            '{% component \'foo\' %}{% block child %}{% component \'bar\' %}{% block message %}Hello World!{% endblock %}{% endcomponent %}{% endblock %}{% endcomponent %}',
        ];
        yield 'component_with_mixture_of_string_and_twig_in_argument' => [
            '<twig:foo text="Hello {{ name }}!"/>',
            "{{ component('foo', { text: 'Hello '~(name)~'!' }) }}",
        ];
        yield 'component_with_mixture_of_dynamic_twig_from_start' => [
            '<twig:foo text="{{ name   }} is my name{{ ending~\'!!\' }}"/>',
            "{{ component('foo', { text: (name)~' is my name'~(ending~'!!') }) }}",
        ];
        yield 'dynamic_attribute_with_quotation_included' => [
            '<twig:foo text="{{ "hello!" }}"/>',
            "{{ component('foo', { text: (\"hello!\") }) }}",
        ];
        yield 'component_with_mixture_of_string_and_twig_with_quote_in_argument' => [
            '<twig:foo text="Hello {{ name }}, I\'m Theo!"/>',
            "{{ component('foo', { text: 'Hello '~(name)~', I\'m Theo!' }) }}",
        ];
        yield 'component_where_entire_default_block_is_embedded_component' => [
            <<<EOF
            <twig:foo>
                <twig:bar>bar content</twig:bar>
            </twig:foo>
            EOF,
            <<<EOF
            {% component 'foo' %}
                {% block content %}{% component 'bar' %}{% block content %}bar content{% endblock %}{% endcomponent %}
            {% endblock %}{% endcomponent %}
            EOF
        ];
        yield 'component_where_entire_default_block_is_embedded_component_self_closing' => [
            <<<EOF
            <twig:foo>
                <twig:bar />
            </twig:foo>
            EOF,
            <<<EOF
            {% component 'foo' %}
                {% block content %}{{ component('bar') }}
            {% endblock %}{% endcomponent %}
            EOF
        ];

        yield 'string_inside_of_twig_code_not_escaped' => [
            <<<EOF
            <twig:TabbedCodeBlocks :files="[
                'src/Twig/MealPlanner.php',
                'templates/components/MealPlanner.html.twig',
            ]" />
            EOF,
            <<<EOF
            {{ component('TabbedCodeBlocks', { files: [
                'src/Twig/MealPlanner.php',
                'templates/components/MealPlanner.html.twig',
            ] }) }}
            EOF
        ];

        yield 'component_with_dashed_attribute' => [
            '<twig:foobar data-action="foo#bar"></twig:foobar>',
            '{% component \'foobar\' with { \'data-action\': \'foo#bar\' } %}{% endcomponent %}',
        ];

        yield 'component_with_dashed_attribute_self_closing' => [
            '<twig:foobar data-action="foo#bar" />',
            '{{ component(\'foobar\', { \'data-action\': \'foo#bar\' }) }}',
        ];

        yield 'component_with_colon_attribute' => [
            '<twig:foobar my:attribute="yo"></twig:foobar>',
            '{% component \'foobar\' with { \'my:attribute\': \'yo\' } %}{% endcomponent %}',
        ];

        yield 'component_with_truthy_attribute' => [
            '<twig:foobar data-turbo-stream></twig:foobar>',
            '{% component \'foobar\' with { \'data-turbo-stream\': true } %}{% endcomponent %}',
        ];

        yield 'component_with_empty_attributes' => [
            '<twig:foobar data-turbo-stream="" my-attribute=\'\'></twig:foobar>',
            '{% component \'foobar\' with { \'data-turbo-stream\': \'\', \'my-attribute\': \'\' } %}{% endcomponent %}',
        ];

        yield 'ignore_twig_comment' => [
            '{# <twig:Alert/> #} <twig:Alert/>',
            '{# <twig:Alert/> #} {{ component(\'Alert\') }}',
        ];

        yield 'file_ended_with_comments' => [
            '{# <twig:Alert/> #}',
            '{# <twig:Alert/> #}',
        ];

        yield 'mixing_component_and_file_ended_with_comments' => [
            '<twig:Alert/> {# <twig:Alert/> #}',
            '{{ component(\'Alert\') }} {# <twig:Alert/> #}',
        ];

        yield 'ignore_content_of_verbatim_block' => [
            '{% verbatim %}<twig:Alert/>{% endverbatim %}',
            '{% verbatim %}<twig:Alert/>{% endverbatim %}',
        ];

        yield 'component_attr_spreading_self_closing' => [
            '<twig:foobar bar="baz"{{...attr}}/>',
            '{{ component(\'foobar\', { bar: \'baz\', ...attr }) }}',
        ];
        yield 'component_attr_spreading_self_closing2' => [
            '<twig:foobar bar="baz"{{ ...customAttrs }} />',
            '{{ component(\'foobar\', { bar: \'baz\', ...customAttrs }) }}',
        ];
        yield 'component_attr_spreading_self_closing3' => [
            '<twig:foobar bar="baz" {{...attr }} />',
            '{{ component(\'foobar\', { bar: \'baz\', ...attr }) }}',
        ];

        yield 'component_attr_spreading_with_content1' => [
            '<twig:foobar bar="baz"{{...attr}}>content</twig:foobar>',
            '{% component \'foobar\' with { bar: \'baz\', ...attr } %}{% block content %}content{% endblock %}{% endcomponent %}',
        ];
        yield 'component_attr_spreading_with_content2' => [
            '<twig:foobar bar="baz"{{ ...customAttrs }}>content</twig:foobar>',
            '{% component \'foobar\' with { bar: \'baz\', ...customAttrs } %}{% block content %}content{% endblock %}{% endcomponent %}',
        ];
        yield 'component_attr_spreading_with_content3' => [
            '<twig:foobar bar="baz" {{ ...attr }}>content</twig:foobar>',
            '{% component \'foobar\' with { bar: \'baz\', ...attr } %}{% block content %}content{% endblock %}{% endcomponent %}',
        ];
    }
}
