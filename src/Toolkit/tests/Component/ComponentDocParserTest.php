<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Component;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Component\ComponentDoc;
use Symfony\UX\Toolkit\Component\ComponentDocParser;

class ComponentDocParserTest extends TestCase
{
    public function testParsesNameTypeAndDescription()
    {
        $doc = new ComponentDocParser()->parse(<<<'TWIG'
            {%- props
                ## string Unique identifier.
                id
            -%}
            TWIG);

        self::assertInstanceOf(ComponentDoc::class, $doc);
        self::assertCount(1, $doc->props);
        self::assertSame('id', $doc->props[0]->name);
        self::assertSame('string', $doc->props[0]->type);
        self::assertSame('Unique identifier.', $doc->props[0]->description);
    }

    public function testDefaultIsSourcedFromThePropsDeclaration()
    {
        $doc = new ComponentDocParser()->parse(<<<'TWIG'
            {%- props
                ## boolean Whether it is open.
                open = false
            -%}
            TWIG);

        self::assertSame('Whether it is open.', $doc->props[0]->description);
        self::assertSame('false', $doc->props[0]->default);
    }

    public function testStringDefaultFromPropsIsQuoted()
    {
        $doc = new ComponentDocParser()->parse(<<<'TWIG'
            {%- props
                ## 'default'|'line' The visual style variant.
                variant = 'default'
            -%}
            TWIG);

        self::assertSame("'default'|'line'", $doc->props[0]->type);
        self::assertSame("'default'", $doc->props[0]->default);
    }

    public function testMultiplePropsAreParsedInDeclarationOrder()
    {
        $doc = new ComponentDocParser()->parse(<<<'TWIG'
            {%- props
                ## string Unique identifier.
                id,
                ## 'default'|'line' The visual style variant.
                variant = 'default',
                ## boolean Whether it is open.
                open = false
            -%}
            TWIG);

        self::assertSame(['id', 'variant', 'open'], array_map(static fn ($p) => $p->name, $doc->props));
        self::assertSame(['string', "'default'|'line'", 'boolean'], array_map(static fn ($p) => $p->type, $doc->props));
    }

    public function testDescriptionKeepsInlineBackticksAndIsWhitespaceNormalized()
    {
        $doc = new ComponentDocParser()->parse(<<<'TWIG'
            {%- props
                ## array List of choices: `[{value: 'x'}]`   or grouped.
                choices = []
            -%}
            TWIG);

        self::assertSame("List of choices: `[{value: 'x'}]` or grouped.", $doc->props[0]->description);
        self::assertSame('[]', $doc->props[0]->default);
    }

    public function testParsesBlocks()
    {
        $doc = new ComponentDocParser()->parse(<<<'TWIG'
            {%- props
                ## string Unique identifier.
                id
            -%}
            <div>
                {## The dialog structure. #}
                {%- block content %}{% endblock -%}
            </div>
            TWIG);

        self::assertCount(1, $doc->blocks);
        self::assertSame('content', $doc->blocks[0]->name);
        self::assertSame('The dialog structure.', $doc->blocks[0]->description);
    }

    public function testReturnsEmptyDocWhenNoDocblocks()
    {
        $doc = new ComponentDocParser()->parse('<div>Nothing here</div>');

        self::assertSame([], $doc->props);
        self::assertSame([], $doc->blocks);
    }
}
