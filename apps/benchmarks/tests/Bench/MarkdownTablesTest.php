<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Bench;

use App\Bench\MarkdownTables;
use PHPUnit\Framework\TestCase;

final class MarkdownTablesTest extends TestCase
{
    public function testItPadsCellsLikeOxfmt(): void
    {
        $markdown = "| a | bbb |\n|---|---|\n| cccc | d |\n";

        $aligned = MarkdownTables::align($markdown);

        self::assertSame("| a    | bbb |\n| ---- | --- |\n| cccc | d   |\n", $aligned);
    }

    public function testItLeavesTextAroundTheTableUntouched(): void
    {
        $markdown = "# Title\n\nSome  text | with a pipe\n\n| a | b |\n|-|-|\n| c | d |\n\n- a list item\n";

        $aligned = MarkdownTables::align($markdown);

        self::assertSame("# Title\n\nSome  text | with a pipe\n\n| a   | b   |\n| --- | --- |\n| c   | d   |\n\n- a list item\n", $aligned);
    }

    public function testItKeepsAMinimumColumnWidthOfThree(): void
    {
        $markdown = "| a |\n| - |\n| b |\n";

        $aligned = MarkdownTables::align($markdown);

        self::assertSame("| a   |\n| --- |\n| b   |\n", $aligned);
    }
}
