<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Bridge\EditorJS\Tests\BlockRenderer;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Bridge\EditorJS\BlockRenderer\ParagraphRenderer;

final class ParagraphRendererTest extends TestCase
{
    public function testTypeAndRender(): void
    {
        $r = new ParagraphRenderer();
        self::assertSame('paragraph', $r->getBlockType());
        self::assertSame('<p>hello</p>', $r->render(['text' => 'hello']));
    }

    public function testEscapesHtml(): void
    {
        self::assertSame('<p>&lt;script&gt;x&lt;/script&gt;</p>', (new ParagraphRenderer())->render(['text' => '<script>x</script>']));
    }

    public function testEmptyTextEmptyParagraph(): void
    {
        self::assertSame('<p></p>', (new ParagraphRenderer())->render([]));
    }
}
