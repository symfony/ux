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
use Symfony\UX\Toolkit\Markdown\PreviewUrlGenerator;
use Symfony\UX\Toolkit\Recipe\Recipe;

final class RecipeDocRendererTest extends KernelTestCase
{
    public function testRenderAsMarkdownResolvesExamplesAndOmitsInteractiveDirectives()
    {
        [$kit, $recipe] = $this->loadPostLinkRecipe();

        $markdown = $this->renderer()->renderAsMarkdown($kit, $recipe);

        // Description + install command (kit id is the kit directory name).
        $this->assertStringContainsString('A link submitted as a form', $markdown);
        $this->assertStringContainsString('ux:install post-link --kit common', $markdown);
        // Portable Markdown: examples resolved to fences, no interactive directives left.
        $this->assertStringNotContainsString('::: example', $markdown);
        $this->assertStringNotContainsString('::: tabs', $markdown);
        $this->assertStringContainsString('```twig', $markdown);
        $this->assertStringContainsString('<twig:PostLink', $markdown);
        // API reference from the component docblocks.
        $this->assertStringContainsString('## API Reference', $markdown);
        $this->assertStringContainsString('<twig:PostLink>', $markdown);
    }

    public function testRenderAsHtmlProducesTabsAndALivePreview()
    {
        [$kit, $recipe] = $this->loadPostLinkRecipe();

        $urlGenerator = new class implements PreviewUrlGenerator {
            public function generate(string $code, array $options): ?string
            {
                return 'https://preview.test/render';
            }
        };

        $html = $this->renderer()->renderAsHtml($kit, $recipe, $urlGenerator);

        $this->assertStringContainsString('toolkit-tabs', $html);
        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringContainsString('src="https://preview.test/render"', $html);
        $this->assertStringContainsString('PostLink', $html);
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
