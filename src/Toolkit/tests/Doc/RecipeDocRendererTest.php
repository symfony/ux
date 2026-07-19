<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Doc;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Toolkit\Doc\RecipeDocRenderer;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Markdown\CodeOptions;
use Symfony\UX\Toolkit\Markdown\PreviewUrlGenerator;
use Symfony\UX\Toolkit\Recipe\Recipe;

final class RecipeDocRendererTest extends KernelTestCase
{
    public function testRenderAsMarkdownResolvesExamplesAndOmitsInteractiveDirectives()
    {
        [$kit, $recipe] = $this->loadPostLinkRecipe();

        $markdown = $this->renderer()->renderAsMarkdown($kit, $recipe);

        // Install command (kit id is the kit directory name).
        $this->assertStringContainsString('ux:install post-link --kit common', $markdown);
        // Portable Markdown: examples resolved to fences, no interactive directives left.
        $this->assertStringNotContainsString('::: example', $markdown);
        $this->assertStringNotContainsString('::: tabs', $markdown);
        $this->assertStringContainsString('```twig', $markdown);
        $this->assertStringContainsString('<twig:PostLink', $markdown);
        // No render metadata (info string) leaks into portable Markdown.
        $this->assertStringNotContainsString('"preview"', $markdown);
        // API reference from the component docblocks.
        $this->assertStringContainsString('## API Reference', $markdown);
        $this->assertStringContainsString('<twig:PostLink>', $markdown);
    }

    public function testRenderAsHtmlProducesTabsAndALivePreview()
    {
        [$kit, $recipe] = $this->loadPostLinkRecipe();

        $urlGenerator = new class implements PreviewUrlGenerator {
            public function generate(string $code, CodeOptions $options): ?string
            {
                return 'https://preview.test/render';
            }
        };

        $rendered = $this->renderer()->renderAsHtml($kit, $recipe, $urlGenerator);

        $this->assertStringContainsString('toolkit-tabs', $rendered->html);
        $this->assertStringContainsString('<iframe', $rendered->html);
        $this->assertStringContainsString('src="https://preview.test/render"', $rendered->html);
        $this->assertStringContainsString('PostLink', $rendered->html);

        $this->assertNotEmpty($rendered->tableOfContents);
        foreach ($rendered->tableOfContents as $item) {
            $this->assertContains($item['level'], [2, 3]);
            $this->assertNotSame('', $item['id']);
            $this->assertNotSame('', $item['title']);
        }
        $this->assertContains('API Reference', array_column($rendered->tableOfContents, 'title'));
    }

    public function testACustomDocMdControlsTheLayoutAndInjectsGeneratedContent()
    {
        [$kit, $recipe] = $this->loadPostLinkRecipe();

        // A recipe can ship a README.md that drives the layout, adds its own prose, and drops in the
        // generated pieces with directives.
        $recipe = new Recipe($recipe->name, $recipe->absolutePath, $recipe->manifest, doc: <<<'MARKDOWN'
            ## Installation

            A custom note before the install steps.

            ::: installation
            MARKDOWN);

        $markdown = $this->renderer()->renderAsMarkdown($kit, $recipe);

        $this->assertStringContainsString('A custom note before the install steps.', $markdown);
        $this->assertStringContainsString('ux:install post-link --kit common', $markdown);
        // The `::: installation` directive is replaced by the generated steps.
        $this->assertStringNotContainsString('::: installation', $markdown);
        // Nothing the author did not ask for: no Demo, no API reference.
        $this->assertStringNotContainsString('## API Reference', $markdown);
        $this->assertStringNotContainsString('```twig', $markdown);
    }

    private function renderer(): RecipeDocRenderer
    {
        return new RecipeDocRenderer(self::getContainer()->get('twig'));
    }

    /**
     * @return array{0: Kit, 1: Recipe}
     */
    private function loadPostLinkRecipe(): array
    {
        $kitFactory = new KitFactory(
            self::getContainer()->get('filesystem'),
            self::getContainer()->get('ux_toolkit.kit.kit_synchronizer'),
        );

        $kit = $kitFactory->createKitFromAbsolutePath(\dirname(__DIR__, 2).'/kits/common');
        $recipe = $kit->getRecipe('post-link');
        $this->assertNotNull($recipe);

        return [$kit, $recipe];
    }
}
