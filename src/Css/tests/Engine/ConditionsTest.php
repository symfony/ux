<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Engine;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\Engine\Breakpoints;
use Symfony\UX\Css\Engine\Conditions;

final class ConditionsTest extends TestCase
{
    private Conditions $conditions;

    protected function setUp(): void
    {
        $this->conditions = new Conditions(
            ['hover' => '&:is(:hover, [data-hover])', 'dark' => '[data-theme=dark] &'],
            new Breakpoints(['sm' => '640px', 'md' => '768px']),
            ['sm' => '24rem'],
            ['sidebar'],
            ['pastel' => []],
        );
    }

    public static function provideKeys(): iterable
    {
        yield 'declared condition' => ['_hover', true];
        yield 'declared condition without underscore' => ['hover', false];
        yield 'breakpoint' => ['sm', true];
        yield 'breakpoint range' => ['smToMd', true];
        yield 'breakpoint down' => ['mdDown', true];
        yield 'container size' => ['@/sm', true];
        yield 'named container size' => ['@sidebar/sm', true];
        yield 'theme' => ['_themePastel', true];
        yield 'base' => ['base', true];
        yield 'arbitrary selector' => ['&:focus', true];
        yield 'arbitrary at-rule' => ['@media print', true];
        yield 'property' => ['color', false];
    }

    #[DataProvider('provideKeys')]
    public function testIsCondition(string $key, bool $expected): void
    {
        $this->assertSame($expected, $this->conditions->isCondition($key));
    }

    public function testFinalizeStripsUnderscoresAndWrapsArbitraryConditions(): void
    {
        $keys = ['_hover', 'md', '&:focus', ' [dir=rtl] & ', '@media print', 'unknown'];

        $finalized = $this->conditions->finalize($keys);

        $this->assertSame(['hover', 'md', '[&:focus]', '[[dir=rtl]_&]', '[@media_print]', 'unknown'], $finalized);
    }
}
