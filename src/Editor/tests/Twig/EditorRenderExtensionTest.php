<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\UX\Editor\Bridge\Format\Block\BlockRendererInterface;
use Symfony\UX\Editor\Bridge\Format\Block\BlockRendererRegistry;
use Symfony\UX\Editor\Content\BlockContent;
use Symfony\UX\Editor\Content\HtmlContent;
use Symfony\UX\Editor\Twig\EditorRenderExtension;

final class EditorRenderExtensionTest extends TestCase
{
    public function testNullReturnsEmpty(): void
    {
        self::assertSame('', new EditorRenderExtension(new BlockRendererRegistry([]), null)->render(null));
    }

    public function testHtmlContentSanitized(): void
    {
        $s = new HtmlSanitizer(new HtmlSanitizerConfig()->allowSafeElements());
        $out = new EditorRenderExtension(new BlockRendererRegistry([]), $s)->render(new HtmlContent('<script>x</script><p>ok</p>'));
        self::assertStringNotContainsString('<script>', $out);
        self::assertStringContainsString('<p>ok</p>', $out);
    }

    public function testHtmlContentNoSanitizerEchosRaw(): void
    {
        self::assertSame('<p>x</p>', new EditorRenderExtension(new BlockRendererRegistry([]), null)->render(new HtmlContent('<p>x</p>')));
    }

    public function testBlockContentWalksRegistry(): void
    {
        $registry = new BlockRendererRegistry([
            new class implements BlockRendererInterface {
                public function getBlockType(): string
                {
                    return 'paragraph';
                }

                public function render(array $blockData, array $blockMeta = []): string
                {
                    return '<p>'.htmlspecialchars($blockData['text'] ?? '').'</p>';
                }
            },
        ]);
        $bc = new BlockContent([['type' => 'paragraph', 'data' => ['text' => 'hello']]]);
        self::assertSame('<p>hello</p>', new EditorRenderExtension($registry, null)->render($bc));
    }

    public function testBlockContentMissingRendererCommentInProd(): void
    {
        $e = new EditorRenderExtension(new BlockRendererRegistry([]), null, debug: false);
        self::assertSame('<!-- ux-editor: missing renderer for "unknown" -->', $e->render(new BlockContent([['type' => 'unknown', 'data' => []]])));
    }

    public function testBlockContentMissingRendererVisibleInDebug(): void
    {
        $e = new EditorRenderExtension(new BlockRendererRegistry([]), null, debug: true);
        $out = $e->render(new BlockContent([['type' => 'unknown', 'data' => []]]));
        self::assertStringContainsString('Missing block renderer', $out);
        self::assertStringContainsString('unknown', $out);
    }
}
