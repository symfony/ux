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
use Symfony\UX\Toolkit\Component\PropsDeclarationParser;

class PropsDeclarationParserTest extends TestCase
{
    public function testReturnsNullWhenNoPropsTag()
    {
        self::assertNull(new PropsDeclarationParser()->parse('<div>{{ foo }}</div>'));
    }

    public function testParsesNamesInDeclarationOrder()
    {
        $decl = new PropsDeclarationParser()->parse('{%- props id, open = false -%}');

        self::assertNotNull($decl);
        self::assertSame(['id', 'open'], $decl->names());
    }

    public function testRequiredPropIsFlaggedAsHavingNoDefault()
    {
        $decl = new PropsDeclarationParser()->parse('{%- props id, open = false -%}');

        self::assertFalse($decl->get('id')->hasDefault);
        self::assertTrue($decl->get('open')->hasDefault);
    }

    public function testBooleanNumberAndNullDefaultsRenderBare()
    {
        $decl = new PropsDeclarationParser()->parse('{%- props open = false, spacing = 2, name = null -%}');

        self::assertSame('false', $decl->get('open')->default);
        self::assertSame('2', $decl->get('spacing')->default);
        self::assertSame('null', $decl->get('name')->default);
    }

    public function testStringDefaultsRenderSingleQuoted()
    {
        $decl = new PropsDeclarationParser()->parse('{%- props variant = \'default\', label = "Loading", value = \'\' -%}');

        self::assertSame("'default'", $decl->get('variant')->default);
        self::assertSame("'Loading'", $decl->get('label')->default);
        self::assertSame("''", $decl->get('value')->default);
    }

    public function testArrayDefaultRendersAsIs()
    {
        $decl = new PropsDeclarationParser()->parse('{%- props choices = [] -%}');

        self::assertSame('[]', $decl->get('choices')->default);
    }

    public function testTopLevelCommaSplitIgnoresCommasInsideBracketsAndStrings()
    {
        $decl = new PropsDeclarationParser()->parse('{%- props items = [\'x\', \'y\'], label = \'a, b\', open = false -%}');

        self::assertSame(['items', 'label', 'open'], $decl->names());
        self::assertSame("['x', 'y']", $decl->get('items')->default);
        self::assertSame("'a, b'", $decl->get('label')->default);
        self::assertSame('false', $decl->get('open')->default);
    }

    public function testSupportsWhitespaceControlAndPlainTagForms()
    {
        $plain = new PropsDeclarationParser()->parse('{% props asIcon = false %}');
        $trimmed = new PropsDeclarationParser()->parse('{%- props asIcon = false -%}');

        self::assertSame('false', $plain->get('asIcon')->default);
        self::assertSame('false', $trimmed->get('asIcon')->default);
    }

    public function testNonLiteralDefaultExpressionIsStillFlaggedAsHavingADefault()
    {
        $decl = new PropsDeclarationParser()->parse('{%- props label = foo ~ bar -%}');

        self::assertTrue($decl->get('label')->hasDefault);
    }

    public function testInlineDocCommentsAreIgnoredWhenParsingNamesAndDefaults()
    {
        $decl = new PropsDeclarationParser()->parse(<<<'TWIG'
            {%- props
                ## string Unique identifier.
                id,
                ## 'default'|'line' The visual style variant.
                variant = 'default'
            -%}
            TWIG);

        self::assertNotNull($decl);
        self::assertSame(['id', 'variant'], $decl->names());
        self::assertFalse($decl->get('id')->hasDefault);
        self::assertSame("'default'", $decl->get('variant')->default);
    }
}
