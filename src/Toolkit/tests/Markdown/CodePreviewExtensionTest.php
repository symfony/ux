<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Markdown;

use League\CommonMark\Renderer\ChildNodeRendererInterface;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Markdown\CodeOptions;
use Symfony\UX\Toolkit\Markdown\Extension\CodePreview\Node\CodePreview;
use Symfony\UX\Toolkit\Markdown\Extension\CodePreview\Renderer\CodePreviewRenderer;
use Symfony\UX\Toolkit\Markdown\PreviewUrlGenerator;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

class CodePreviewExtensionTest extends TestCase
{
    public function testRendersLiveIframeWhenUrlGeneratorReturnsUrl()
    {
        $urlGenerator = new class implements PreviewUrlGenerator {
            public function generate(string $code, CodeOptions $options): ?string
            {
                return 'https://example.test/preview';
            }
        };

        $html = $this->render(new CodePreview('<twig:Button>Hi</twig:Button>', new CodeOptions()), $urlGenerator);

        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('src="https://example.test/preview"', $html);
    }

    public function testRendersStaticBlockWhenNoUrlGeneratorConfigured()
    {
        $html = $this->render(new CodePreview('<twig:Button>Hi & bye</twig:Button>'), null);

        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringContainsString('<pre', $html);
        // The code is shown verbatim and HTML-escaped, never executed.
        $this->assertStringContainsString('&lt;twig:Button&gt;Hi &amp; bye&lt;/twig:Button&gt;', $html);
    }

    private function render(CodePreview $node, ?PreviewUrlGenerator $urlGenerator): string
    {
        $loader = new FilesystemLoader();
        $loader->addPath(\dirname(__DIR__, 2).'/templates', 'UXToolkit');
        $twig = new TwigEnvironment($loader);

        $renderer = new CodePreviewRenderer($twig, $urlGenerator);

        return $renderer->render($node, $this->createStub(ChildNodeRendererInterface::class));
    }
}
