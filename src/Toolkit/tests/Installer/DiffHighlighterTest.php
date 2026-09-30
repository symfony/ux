<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Installer;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Installer\DiffHighlighter;

final class DiffHighlighterTest extends TestCase
{
    public function testShouldHighlightTheChangedPartOfModifiedLines(): void
    {
        $diff = <<<'DIFF'
            @@ -1,2 +1,2 @@
             {%- props
            -    variant = 'outline',
            +    variant = 'default',

            DIFF;

        $lines = new DiffHighlighter()->highlight($diff);

        $this->assertSame([
            '<fg=cyan>@@ -1,2 +1,2 @@</>',
            ' {%- props',
            "<fg=red>-    variant = '</><fg=red;options=reverse>outline</><fg=red>',</>",
            "<fg=green>+    variant = '</><fg=green;options=reverse>default</><fg=green>',</>",
        ], $lines);
    }

    public function testShouldNotHighlightTheSideWithoutChangedPart(): void
    {
        $diff = <<<'DIFF'
            @@ -1 +1 @@
            -    base: "test group/alert",
            +    base: "group/alert",

            DIFF;

        $lines = new DiffHighlighter()->highlight($diff);

        $this->assertSame([
            '<fg=cyan>@@ -1 +1 @@</>',
            '<fg=red>-    base: "</><fg=red;options=reverse>test </><fg=red>group/alert",</>',
            '<fg=green>+    base: "group/alert",</>',
        ], $lines);
    }

    public function testShouldNotHighlightLinesWithNothingInCommon(): void
    {
        $diff = <<<'DIFF'
            @@ -1 +1 @@
            -foo
            +bar

            DIFF;

        $lines = new DiffHighlighter()->highlight($diff);

        $this->assertSame([
            '<fg=cyan>@@ -1 +1 @@</>',
            '<fg=red>-foo</>',
            '<fg=green>+bar</>',
        ], $lines);
    }

    public function testShouldNotHighlightBlocksWithDifferentLineCounts(): void
    {
        $diff = <<<'DIFF'
            @@ -1,2 +1 @@
            -foo = 1
            -foo = 2
            +foo = 3

            DIFF;

        $lines = new DiffHighlighter()->highlight($diff);

        $this->assertSame([
            '<fg=cyan>@@ -1,2 +1 @@</>',
            '<fg=red>-foo = 1</>',
            '<fg=red>-foo = 2</>',
            '<fg=green>+foo = 3</>',
        ], $lines);
    }

    public function testShouldHighlightEachBlockOfAHunkSeparately(): void
    {
        $diff = <<<'DIFF'
            @@ -1,3 +1,3 @@
            -a = 1
            +a = 2
             b
            -c = 1
            +c = 2

            DIFF;

        $lines = new DiffHighlighter()->highlight($diff);

        $this->assertSame([
            '<fg=cyan>@@ -1,3 +1,3 @@</>',
            '<fg=red>-a = </><fg=red;options=reverse>1</>',
            '<fg=green>+a = </><fg=green;options=reverse>2</>',
            ' b',
            '<fg=red>-c = </><fg=red;options=reverse>1</>',
            '<fg=green>+c = </><fg=green;options=reverse>2</>',
        ], $lines);
    }

    public function testShouldNotSplitMultibyteCharacters(): void
    {
        $diff = <<<'DIFF'
            @@ -1 +1 @@
            -passé
            +passè

            DIFF;

        $lines = new DiffHighlighter()->highlight($diff);

        $this->assertSame([
            '<fg=cyan>@@ -1 +1 @@</>',
            '<fg=red>-pass</><fg=red;options=reverse>é</>',
            '<fg=green>+pass</><fg=green;options=reverse>è</>',
        ], $lines);
    }

    public function testShouldSkipTheHeaderAndEscapeTheContent(): void
    {
        $diff = <<<'DIFF'
            diff --git a/local.html.twig b/recipe.html.twig
            index 1111111..2222222 100644
            --- a/local.html.twig
            +++ b/recipe.html.twig
            @@ -1,2 +1,2 @@
             <div>
            -<info>foo</info>
            +<info>bar</info>

            DIFF;

        $lines = new DiffHighlighter()->highlight($diff);

        $this->assertSame([
            '<fg=cyan>@@ -1,2 +1,2 @@</>',
            ' \<div\>',
            '<fg=red>-\<info\></><fg=red;options=reverse>foo</><fg=red>\</info\></>',
            '<fg=green>+\<info\></><fg=green;options=reverse>bar</><fg=green>\</info\></>',
        ], $lines);
    }
}
