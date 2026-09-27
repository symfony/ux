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

use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\Engine\Breakpoints;

final class BreakpointsTest extends TestCase
{
    public function testKeysStartWithBaseAndFollowTheBreakpointValues(): void
    {
        $breakpoints = new Breakpoints(['md' => '768px', 'sm' => '640px', 'xl' => '1280px']);

        $this->assertSame(['base', 'sm', 'md', 'xl'], $breakpoints->keys);
    }

    public function testBreakpointsWithTheSameIntegerPartKeepTheirOrder(): void
    {
        $breakpoints = new Breakpoints(['sm' => '40rem', 'md' => '40.5rem', 'lg' => '64rem']);

        $this->assertSame(['base', 'sm', 'md', 'lg'], $breakpoints->keys);
    }

    public function testConditionNamesMatchPandaRuntime(): void
    {
        $breakpoints = new Breakpoints([
            'sm' => '640px',
            'md' => '768px',
            'lg' => '1024px',
            'xl' => '1280px',
            '2xl' => '1536px',
        ]);

        $this->assertSame([
            'sm', 'smOnly', 'smDown',
            'md', 'mdOnly', 'mdDown',
            'lg', 'lgOnly', 'lgDown',
            'xl', 'xlOnly', 'xlDown',
            '2xl', '2xlOnly', '2xlDown',
            'smToMd', 'smToLg', 'smToXl', 'smTo2xl', 'mdToLg', 'mdToXl', 'mdTo2xl', 'lgToXl', 'lgTo2xl', 'xlTo2xl',
        ], $breakpoints->getConditionNames());
    }

    public function testNoBreakpointsOnlyKeepBase(): void
    {
        $breakpoints = new Breakpoints([]);

        $this->assertSame(['base'], $breakpoints->keys);
        $this->assertSame([], $breakpoints->getConditionNames());
    }
}
