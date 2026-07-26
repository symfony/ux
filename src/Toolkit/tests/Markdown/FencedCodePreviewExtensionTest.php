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

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\MarkdownConverter;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Toolkit\Markdown\CodeOptions;
use Symfony\UX\Toolkit\Markdown\Extension\CodePreview\CodePreviewExtension;
use Symfony\UX\Toolkit\Markdown\Extension\FencedCodePreview\FencedCodePreviewExtension;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\TabsExtension;
use Symfony\UX\Toolkit\Markdown\PreviewUrlGenerator;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

class FencedCodePreviewExtensionTest extends TestCase
{
    public function testPreviewFlaggedFenceBecomesPreviewAndCodeTabs()
    {
        $html = $this->convert(<<<'MARKDOWN'
            ```twig {"preview": true, "height": "300px"}
            <twig:Button>Hi</twig:Button>
            ```
            MARKDOWN);

        $this->assertStringContainsString('toolkit-tabs', $html);
        $this->assertStringContainsString('>Preview<', $html);
        $this->assertStringContainsString('>Code<', $html);
        $this->assertStringContainsString('toolkit-code-preview--static', $html);
        $this->assertStringContainsString('language-twig', $html);
        $this->assertStringContainsString('twig:Button', $html);
    }

    public function testPlainFenceIsLeftUntouched()
    {
        $html = $this->convert(<<<'MARKDOWN'
            ```twig
            <twig:Button>Hi</twig:Button>
            ```
            MARKDOWN);

        $this->assertStringNotContainsString('toolkit-tabs', $html);
        $this->assertStringContainsString('language-twig', $html);
    }

    public function testJsonOptionsAreThreadedToTheLivePreview()
    {
        $urlGenerator = new class implements PreviewUrlGenerator {
            public function generate(string $code, CodeOptions $options): ?string
            {
                return 'https://preview.test/?cc='.$options->collapseClass;
            }
        };

        $html = $this->convert(<<<'MARKDOWN'
            ```twig {"preview": true, "collapseClass": true}
            <b>x</b>
            ```
            MARKDOWN, $urlGenerator);

        $this->assertStringContainsString('src="https://preview.test/?cc=1"', $html);
    }

    private function convert(string $markdown, ?PreviewUrlGenerator $urlGenerator = null): string
    {
        $loader = new FilesystemLoader();
        $loader->addPath(\dirname(__DIR__, 2).'/templates', 'UXToolkit');
        $twig = new TwigEnvironment($loader);

        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TabsExtension($twig));
        $environment->addExtension(new CodePreviewExtension($twig, $urlGenerator));
        $environment->addExtension(new FencedCodePreviewExtension());

        return (string) new MarkdownConverter($environment)->convert($markdown);
    }
}
