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

    public function getLexTests(): iterable
    {
        yield 'simple_component' => [
            '<twig:foo />',
            '{% twig_component \'foo\' %}{% end_twig_component %}',
        ];

        yield 'component_with_attributes' => [
            '<twig:foo bar="baz" with_quotes="It\'s with quotes" />',
            "{% twig_component 'foo' with { bar: 'baz', with_quotes: 'It\'s with quotes' } %}{% end_twig_component %}",
        ];

        yield 'component_with_dynamic_attributes' => [
            '<twig:foo dynamic="{{ dynamicVar }}" :otherDynamic="anotherVar" />',
            '{% twig_component \'foo\' with { dynamic: (dynamicVar), otherDynamic: anotherVar } %}{% end_twig_component %}',
        ];

        yield 'component_with_closing_tag' => [
            '<twig:foo></twig:foo>',
            '{% twig_component \'foo\' %}{% end_twig_component %}',
        ];

        yield 'component_with_block' => [
            '<twig:foo><twig:block name="foo_block">Foo</twig:block></twig:foo>',
            '{% twig_component \'foo\' %}{% block foo_block %}Foo{% endblock %}{% end_twig_component %}',
        ];

        yield 'component_with_traditional_block' => [
            '<twig:foo>{% block foo_block %}Foo{% endblock %}</twig:foo>',
            '{% twig_component \'foo\' %}{% block foo_block %}Foo{% endblock %}{% end_twig_component %}',
        ];

        yield 'traditional_blocks_around_component_do_not_confuse' => [
            'Hello {% block foo_block %}Foo{% endblock %}<twig:foo />{% block bar_block %}Bar{% endblock %}',
            'Hello {% block foo_block %}Foo{% endblock %}{% twig_component \'foo\' %}{% end_twig_component %}{% block bar_block %}Bar{% endblock %}',
        ];

        yield 'component_with_embedded_component_inside_block' => [
            '<twig:foo><twig:block name="foo_block"><twig:bar /></twig:block></twig:foo>',
            '{% twig_component \'foo\' %}{% block foo_block %}{% twig_component \'bar\' %}{% end_twig_component %}{% endblock %}{% end_twig_component %}',
        ];

        yield 'attribute_with_no_value' => [
            '<twig:foo bar />',
            '{% twig_component \'foo\' with { bar: true } %}{% end_twig_component %}',
        ];

        yield 'attribute_with_no_value_and_no_attributes' => [
            '<twig:foo/>',
            '{% twig_component \'foo\' %}{% end_twig_component %}',
        ];

        yield 'component_with_default_block_content' => [
            '<twig:foo>Foo</twig:foo>',
            '{% twig_component \'foo\' %}{% block content %}Foo{% endblock %}{% end_twig_component %}',
        ];

        yield 'component_with_default_block_that_holds_a_component_and_multi_blocks' => [
            '<twig:foo>Foo <twig:bar /><twig:block name="other_block">Other block</twig:block></twig:foo>',
            '{% twig_component \'foo\' %}{% block content %}Foo {% twig_component \'bar\' %}{% end_twig_component %}{% endblock %}{% block other_block %}Other block{% endblock %}{% end_twig_component %}',
        ];
        yield 'component_with_character_:_on_his_name' => [
            '<twig:foo:bar></twig:foo:bar>',
            '{% twig_component \'foo:bar\' %}{% end_twig_component %}',
        ];
        yield 'component_with_character_@_on_his_name' => [
            '<twig:@foo></twig:@foo>',
            '{% twig_component \'@foo\' %}{% end_twig_component %}',
        ];
        yield 'component_with_character_-_on_his_name' => [
            '<twig:foo-bar></twig:foo-bar>',
            '{% twig_component \'foo-bar\' %}{% end_twig_component %}',
        ];
        yield 'component_with_character_._on_his_name' => [
            '<twig:foo.bar></twig:foo.bar>',
            '{% twig_component \'foo.bar\' %}{% end_twig_component %}',
        ];
        yield 'nested_component_2_levels' => [
            '<twig:foo><twig:block name="child"><twig:bar><twig:block name="message">Hello World!</twig:block></twig:bar></twig:block></twig:foo>',
            '{% twig_component \'foo\' %}{% block child %}{% twig_component \'bar\' %}{% block message %}Hello World!{% endblock %}{% end_twig_component %}{% endblock %}{% end_twig_component %}',
        ];
        yield 'component_with_mixture_of_string_and_twig_in_argument' => [
            '<twig:foo text="Hello {{ name }}!"/>',
            "{% twig_component 'foo' with { text: 'Hello '~(name)~'!' } %}{% end_twig_component %}",
        ];
        yield 'component_with_mixture_of_dynamic_twig_from_start' => [
            '<twig:foo text="{{ name   }} is my name{{ ending~\'!!\' }}"/>',
            "{% twig_component 'foo' with { text: (name)~' is my name'~(ending~'!!') } %}{% end_twig_component %}",
        ];
        yield 'dynamic_attribute_with_quotation_included' => [
            '<twig:foo text="{{ "hello!" }}"/>',
            "{% twig_component 'foo' with { text: (\"hello!\") } %}{% end_twig_component %}",
        ];
        yield 'component_with_mixture_of_string_and_twig_with_quote_in_argument' => [
            '<twig:foo text="Hello {{ name }}, I\'m Theo!"/>',
            "{% twig_component 'foo' with { text: 'Hello '~(name)~', I\'m Theo!' } %}{% end_twig_component %}",
        ];
        yield 'component_where_entire_default_block_is_embedded_component' => [
            <<<EOF
            <twig:foo>
                <twig:bar>bar content</twig:bar>
            </twig:foo>
            EOF,
            <<<EOF
            {% twig_component 'foo' %}
                {% block content %}{% twig_component 'bar' %}{% block content %}bar content{% endblock %}{% end_twig_component %}
            {% endblock %}{% end_twig_component %}
            EOF
        ];
        yield 'component_where_entire_default_block_is_embedded_component_self_closing' => [
            <<<EOF
            <twig:foo>
                <twig:bar />
            </twig:foo>
            EOF,
            <<<EOF
            {% twig_component 'foo' %}
                {% block content %}{% twig_component 'bar' %}{% end_twig_component %}
            {% endblock %}{% end_twig_component %}
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
            {% twig_component 'TabbedCodeBlocks' with { files: [
                'src/Twig/MealPlanner.php',
                'templates/components/MealPlanner.html.twig',
            ] } %}{% end_twig_component %}
            EOF
        ];

        yield 'component_with_dashed_attribute' => [
            '<twig:foobar data-action="foo#bar"></twig:foobar>',
            '{% twig_component \'foobar\' with { \'data-action\': \'foo#bar\' } %}{% end_twig_component %}',
        ];

        yield 'component_with_dashed_attribute_self_closing' => [
            '<twig:foobar data-action="foo#bar" />',
            '{% twig_component \'foobar\' with { \'data-action\': \'foo#bar\' } %}{% end_twig_component %}',
        ];

        yield 'component_with_colon_attribute' => [
            '<twig:foobar my:attribute="yo"></twig:foobar>',
            '{% twig_component \'foobar\' with { \'my:attribute\': \'yo\' } %}{% end_twig_component %}',
        ];

        yield 'component_with_truthy_attribute' => [
            '<twig:foobar data-turbo-stream></twig:foobar>',
            '{% twig_component \'foobar\' with { \'data-turbo-stream\': true } %}{% end_twig_component %}',
        ];

        yield 'ignore_twig_comment' => [
            '{# <twig:Alert/> #} <twig:Alert/>',
            '{# <twig:Alert/> #} {% twig_component \'Alert\' %}{% end_twig_component %}',
        ];

        yield 'file_ended_with_comments' => [
            '{# <twig:Alert/> #}',
            '{# <twig:Alert/> #}',
        ];

        yield 'mixing_component_and_file_ended_with_comments' => [
            '<twig:Alert/> {# <twig:Alert/> #}',
            '{% twig_component \'Alert\' %}{% end_twig_component %} {# <twig:Alert/> #}',
        ];
        yield 'slot_inside_block_content' => [
            '<twig:Alert>{% block footer %}{{ user }}{% endblock %}<p>Hello</p><twig:slot name="message">You have a new message</twig:slot></twig:Alert>',
            '{% twig_component \'Alert\' %}{% block footer %}{{ user }}{% endblock %}{% block content %}<p>Hello</p>{% slot message %}You have a new message{% endslot %}{% endblock %}{% end_twig_component %}',
        ];
    }
}
