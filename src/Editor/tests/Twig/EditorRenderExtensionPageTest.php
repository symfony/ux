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
use Symfony\UX\Editor\Bridge\Format\Block\BlockRendererRegistry;
use Symfony\UX\Editor\Content\PageContent;
use Symfony\UX\Editor\Twig\EditorRenderExtension;

final class EditorRenderExtensionPageTest extends TestCase
{
    public function testPageProducesSandboxedIframe(): void
    {
        $out = new EditorRenderExtension(new BlockRendererRegistry([]), null)->render(new PageContent('<h1>X</h1>', 'h1{color:red}'));
        self::assertStringContainsString('<iframe', $out);
        self::assertStringContainsString('sandbox="allow-same-origin"', $out);
        self::assertStringContainsString('srcdoc=', $out);
        self::assertStringContainsString('&lt;h1&gt;X&lt;/h1&gt;', $out);
        self::assertStringContainsString('h1{color:red}', $out);
    }

    public function testEmptyPageIsEmpty(): void
    {
        self::assertSame('', new EditorRenderExtension(new BlockRendererRegistry([]), null)->render(new PageContent('')));
    }
}
