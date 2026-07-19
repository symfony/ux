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
use Symfony\UX\Toolkit\Markdown\Extension\CodePreview\CodePreviewExtension;
use Symfony\UX\Toolkit\Markdown\Extension\Example\ExampleExtension;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\TabsExtension;
use Symfony\UX\Toolkit\Markdown\PreviewUrlGenerator;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeManifest;
use Symfony\UX\Toolkit\Recipe\RecipeType;
use Twig\Environment as TwigEnvironment;
use Twig\Loader\FilesystemLoader;

class ExampleDirectiveTest extends TestCase
{
    public function testRendersExampleAsPreviewAndCodeTabs()
    {
        $html = $this->convert('::: example Basic');

        // Rendered as a Tabs block with a Preview and a Code tab.
        $this->assertStringContainsString('toolkit-tabs', $html);
        $this->assertStringContainsString('>Preview<', $html);
        $this->assertStringContainsString('>Code<', $html);
        // Preview tab holds a (static, since no URL generator) CodePreview; Code tab holds a fenced code block.
        $this->assertStringContainsString('toolkit-code-preview--static', $html);
        $this->assertStringContainsString('language-twig', $html);
        // The example code is present (escaped), never executed.
        $this->assertStringContainsString('Hello from Basic', $html);
    }

    public function testPassesJsonOptionsThroughToTheLivePreview()
    {
        $urlGenerator = new class implements PreviewUrlGenerator {
            public function generate(string $code, array $options): ?string
            {
                return 'https://preview.test/?h='.($options['height'] ?? 'none');
            }
        };

        $html = $this->convert('::: example Basic {"height": "300px"}', $urlGenerator);

        $this->assertStringContainsString('src="https://preview.test/?h=300px"', $html);
    }

    public function testCodeContainingFencesAndColonsDoesNotBreakParsing()
    {
        $html = $this->convert('::: example Tricky');

        // The example contains ``` fences and ::: colons; building the AST directly (no re-parse)
        // means they appear verbatim without breaking the surrounding fence/container.
        $this->assertStringContainsString('toolkit-tabs', $html);
        $this->assertStringContainsString('language-twig', $html);
        $this->assertStringContainsString('backtick-test', $html);
    }

    public function testMissingExampleThrows()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->convert('::: example DoesNotExist');
    }

    public function testExampleNameCannotEscapeTheExamplesDirectory()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->convert('::: example ../../../../etc/passwd');
    }

    private function convert(string $markdown, ?PreviewUrlGenerator $urlGenerator = null): string
    {
        $loader = new FilesystemLoader();
        $loader->addPath(\dirname(__DIR__, 2).'/templates', 'UXToolkit');
        $twig = new TwigEnvironment($loader);

        $recipe = new Recipe(
            'example-recipe',
            \dirname(__DIR__).'/Fixtures/markdown/example-recipe',
            new RecipeManifest(RecipeType::Component, 'example-recipe', 'A fixture recipe.', []),
        );

        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new TabsExtension($twig));
        $environment->addExtension(new CodePreviewExtension($twig, $urlGenerator));
        $environment->addExtension(new ExampleExtension($recipe));

        return (string) new MarkdownConverter($environment)->convert($markdown);
    }
}
