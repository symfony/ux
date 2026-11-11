<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Breadcrumb\LabelPattern;

#[CoversClass(LabelPattern::class)]
final class LabelPatternTest extends TestCase
{
    public function testABareVariableNamesAnArgumentOrARouteParameter(): void
    {
        self::assertSame(
            [['placeholder' => '{slug}', 'variable' => 'slug', 'argument' => null, 'path' => null]],
            LabelPattern::parse('#{slug}'),
        );
    }

    public function testAMappedVariableNamesThePropertyOfAnArgument(): void
    {
        self::assertSame(
            [['placeholder' => '{name:product}', 'variable' => 'name', 'argument' => 'product', 'path' => null]],
            LabelPattern::parse('{name:product}'),
        );
    }

    public function testAPropertyPathMakesTheVariableAnAlias(): void
    {
        self::assertSame(
            [
                ['placeholder' => '{title:product.name}', 'variable' => 'title', 'argument' => 'product', 'path' => 'name'],
                ['placeholder' => '{a:b.c.d}', 'variable' => 'a', 'argument' => 'b', 'path' => 'c.d'],
            ],
            LabelPattern::parse('Edit {title:product.name} in {a:b.c.d}'),
        );
    }

    #[TestWith(['product.view.breadcrumb'])]
    #[TestWith(['{ name }'])]
    #[TestWith(['{}'])]
    #[TestWith(['{count, plural, one {# item} other {# items}}'])]
    public function testAnythingElseIsNotAPattern(string $label): void
    {
        self::assertSame([], LabelPattern::parse($label));
    }

    public function testArgumentsListsEachArgumentOnce(): void
    {
        self::assertSame(['slug', 'product'], LabelPattern::arguments('{slug} {name:product} {title:product.name}'));
    }
}
