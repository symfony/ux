<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Engine\Css;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\Engine\Css\AtRule;
use Symfony\UX\Css\Engine\Css\Declaration;
use Symfony\UX\Css\Engine\Css\Optimizer;
use Symfony\UX\Css\Engine\Css\Root;
use Symfony\UX\Css\Engine\Css\Rule;

final class OptimizerTest extends TestCase
{
    public function testAnEmptyLayerPrintsNothing(): void
    {
        $root = self::root();

        $css = new Optimizer()->toCss($root);

        $this->assertSame('', $css);
    }

    public function testRulesWithIdenticalDeclarationsAreMergedLikePanda(): void
    {
        $root = self::root(
            self::rule('.focus\:c_red:is(:focus, [data-focus])', ['color' => 'red']),
            self::rule('.hover\:c_red:is(:hover, [data-hover])', ['color' => 'red']),
        );

        $css = new Optimizer()->toCss($root);

        $this->assertSame("@layer utilities {\n\n  .focus\\:c_red:is(:focus, [data-focus]),.hover\\:c_red:is(:hover, [data-hover]) {\n    color: red;\n}\n}", $css);
    }

    public function testMediaQueriesWithTheSameParamsAreMerged(): void
    {
        $root = self::root(
            self::rule('.c_red', ['color' => 'red']),
            self::media('screen and (min-width: 48rem)', self::rule('.md\:bg_red', ['background' => 'red'])),
            self::media('screen and (min-width: 48rem)', self::rule('.md\:c_red', ['color' => 'red'])),
        );

        $css = new Optimizer()->toCss($root);

        $this->assertSame("@layer utilities {\n  .c_red {\n    color: red;\n}\n\n  @media screen and (min-width: 48rem) {\n    .md\\:bg_red {\n      background: red;\n}\n    .md\\:c_red {\n      color: red;\n}\n}\n}", $css);
    }

    public function testMediaQueriesMoveAfterOtherNodesButSupportsDoesNot(): void
    {
        $root = self::root(
            self::media('screen and (min-width: 48rem)', self::rule('.md\:c_green', ['color' => 'green'])),
            self::rule('.bg_blue', ['background' => 'blue']),
            new AtRule('supports', '(display: grid)', [self::rule('.supports', ['color' => 'red'])]),
        );

        $css = new Optimizer()->toCss($root);

        $this->assertSame("@layer utilities {\n  .bg_blue {\n    background: blue;\n}\n\n  @supports (display: grid) {\n    .supports {\n      color: red;\n}\n}\n\n  @media screen and (min-width: 48rem) {\n    .md\\:c_green {\n      color: green;\n}\n}\n}", $css);
    }

    public function testMediaQueriesAreSortedMobileFirst(): void
    {
        $root = self::root(
            self::media('screen and (min-width: 64rem)', self::rule('.lg\:c_red', ['color' => 'red'])),
            self::media('screen and (min-width: 40rem)', self::rule('.sm\:c_red', ['color' => 'blue'])),
        );

        $css = new Optimizer()->toCss($root);

        $this->assertSame("@layer utilities {\n  @media screen and (min-width: 40rem) {\n    .sm\\:c_red {\n      color: blue;\n}\n}\n\n  @media screen and (min-width: 64rem) {\n    .lg\\:c_red {\n      color: red;\n}\n}\n}", $css);
    }

    public function testRulesWithSeveralDeclarationsKeepOneLinePerDeclaration(): void
    {
        $root = self::root(
            self::rule('.sr_true', ['position' => 'absolute', 'width' => '1px']),
            self::rule('.c_red', ['color' => 'red']),
        );

        $css = new Optimizer()->toCss($root);

        $this->assertSame("@layer utilities {\n  .sr_true {\n    position: absolute;\n    width: 1px;\n}\n\n  .c_red {\n    color: red;\n}\n}", $css);
    }

    public function testDuplicateRulesKeepTheLastOneLikePostcss(): void
    {
        $root = self::root(
            self::rule('.c_red', ['color' => 'red']),
            self::rule('.c_red', ['color' => 'red']),
        );

        $css = new Optimizer()->toCss($root);

        $this->assertSame("@layer utilities {\n\n  .c_red {\n    color: red;\n}\n}", $css);
    }

    private static function root(Rule|AtRule ...$nodes): Root
    {
        return new Root([new AtRule('layer', 'utilities', $nodes)]);
    }

    private static function media(string $params, Rule ...$rules): AtRule
    {
        return new AtRule('media', $params, $rules);
    }

    /**
     * @param array<string, string> $declarations
     */
    private static function rule(string $selector, array $declarations): Rule
    {
        $nodes = [];
        foreach ($declarations as $property => $value) {
            $nodes[] = new Declaration($property, $value);
        }

        return new Rule($selector, $nodes);
    }
}
