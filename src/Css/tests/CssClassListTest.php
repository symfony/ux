<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\CssClassList;
use Twig\Error\RuntimeError;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class CssClassListTest extends TestCase
{
    public function testItPrintsItsClassesInOrder(): void
    {
        $list = new CssClassList(['p_md' => 'padding', 'c_primary' => 'color']);

        $this->assertSame('p_md c_primary', (string) $list);
        $this->assertSame('p_md c_primary', $list->getValue());
    }

    public function testAnEmptyListHasNoValue(): void
    {
        $list = new CssClassList([]);

        $this->assertNull($list->getValue());
        $this->assertSame('', (string) $list);
    }

    public function testTwoClassesWithTheSameKeyInOneListBothStay(): void
    {
        $list = new CssClassList(['hover:c_a' => 'hover:color', 'hover:c_b' => 'hover:color']);

        $this->assertSame('hover:c_a hover:c_b', (string) $list);
    }

    public function testTheRightListWinsPerKey(): void
    {
        $left = new CssClassList(['p_md' => 'padding', 'c_primary' => 'color']);
        $right = new CssClassList(['p_lg' => 'padding']);

        $this->assertSame('c_primary p_lg', (string) $right->mergeInto($left));
    }

    public function testAConditionIsAnotherKey(): void
    {
        $left = new CssClassList(['p_md' => 'padding']);
        $right = new CssClassList(['hover:p_lg' => 'hover:padding']);

        $this->assertSame('p_md hover:p_lg', (string) $right->mergeInto($left));
    }

    public function testAShorthandAndItsLonghandAreDifferentKeys(): void
    {
        $left = new CssClassList(['p_md' => 'padding']);
        $right = new CssClassList(['px_5' => 'paddingInline']);

        $this->assertSame('p_md px_5', (string) $right->mergeInto($left));
    }

    public function testAnEmptyRightListKeepsTheLeftOne(): void
    {
        $left = new CssClassList(['p_md' => 'padding']);
        $right = new CssClassList([]);

        $this->assertSame('p_md', (string) $right->mergeInto($left));
    }

    public function testAPreviousStringIsKeptBefore(): void
    {
        $list = new CssClassList(['p_lg' => 'padding']);

        $this->assertSame('card p_lg', (string) $list->mergeInto('card'));
    }

    public function testAPreviousStringableIsKeptBefore(): void
    {
        $list = new CssClassList(['p_lg' => 'padding']);
        $previous = new class implements \Stringable {
            public function __toString(): string
            {
                return 'card';
            }
        };

        $this->assertSame('card p_lg', (string) $list->mergeInto($previous));
    }

    public function testANewStringIsAppended(): void
    {
        $list = new CssClassList(['p_md' => 'padding']);

        $this->assertSame('p_md card', (string) $list->appendFrom('card'));
    }

    public function testStringsAreNeverDeduplicated(): void
    {
        $list = new CssClassList(['p_md' => 'padding']);

        $this->assertSame('p_md p_md', (string) $list->appendFrom('p_md'));
    }

    #[DataProvider('provideEmptyValues')]
    public function testEmptyValuesAreIgnored(mixed $value): void
    {
        $list = new CssClassList(['p_md' => 'padding']);

        $this->assertSame('p_md', (string) $list->mergeInto($value));
        $this->assertSame('p_md', (string) $list->appendFrom($value));
    }

    public static function provideEmptyValues(): iterable
    {
        yield 'null' => [null];
        yield 'false' => [false];
        yield 'empty string' => [''];
    }

    #[DataProvider('provideUnmergeableValues')]
    public function testOtherValuesCannotBeMerged(mixed $value): void
    {
        $list = new CssClassList(['p_md' => 'padding']);

        $this->expectException(RuntimeError::class);

        $list->mergeInto($value);
    }

    #[DataProvider('provideUnmergeableValues')]
    public function testOtherValuesCannotBeAppended(mixed $value): void
    {
        $list = new CssClassList(['p_md' => 'padding']);

        $this->expectException(RuntimeError::class);

        $list->appendFrom($value);
    }

    public static function provideUnmergeableValues(): iterable
    {
        yield 'true' => [true];
        yield 'int' => [1];
        yield 'array' => [['a']];
        yield 'object' => [new \stdClass()];
    }

    public function testMergingReturnsANewListAndKeepsBothOperands(): void
    {
        $left = new CssClassList(['p_md' => 'padding', 'c_primary' => 'color']);
        $right = new CssClassList(['p_lg' => 'padding']);

        $merged = $right->mergeInto($left);

        $this->assertNotSame($left, $merged);
        $this->assertNotSame($right, $merged);
        $this->assertSame('p_md c_primary', (string) $left);
        $this->assertSame('p_lg', (string) $right);
    }

    public function testAMergedListMergesAgain(): void
    {
        $a = new CssClassList(['p_md' => 'padding', 'c_primary' => 'color']);
        $b = new CssClassList(['p_lg' => 'padding']);
        $c = new CssClassList(['c_fg' => 'color']);

        $merged = $c->mergeInto($b->mergeInto($a));

        $this->assertSame('p_lg c_fg', (string) $merged);
    }

    public function testItIsEncodedAsItsClassString(): void
    {
        $list = new CssClassList(['p_md' => 'padding']);

        $this->assertSame('"p_md"', json_encode($list));
    }
}
