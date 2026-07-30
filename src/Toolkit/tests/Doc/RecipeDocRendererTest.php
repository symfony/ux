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

    public function testRenderAsMarkdownIncludesStimulusControllerApiReference()
    {
        [$kit, $recipe] = $this->loadWidgetRecipe();

        $markdown = $this->renderer()->renderAsMarkdown($kit, $recipe);

        $this->assertStringContainsString('## API Reference', $markdown);
        // The controller identifier is derived from the `*_controller.js` filename.
        $this->assertStringContainsString('`data-controller="widget"`', $markdown);
        // Value: name/type from the code, `data-*` attribute derived (camelCase to kebab-case), description from the `@value` tag.
        $this->assertStringContainsString('`data-widget-auto-close-value`', $markdown);
        $this->assertStringContainsString('Delay in milliseconds before the widget closes.', $markdown);
        // Target from `static targets`, described by the `@target` tag.
        $this->assertStringContainsString('| `panel` |', $markdown);
        // Class from `static classes`, rendered by its derived `data-*-class` attribute and `@css-class` description.
        $this->assertStringContainsString('| `data-widget-open-class` | Applied to the widget while it is open. |', $markdown);
        // Outlet from `static outlets`, rendered by its derived `data-*-outlet` attribute and `@outlet` description.
        $this->assertStringContainsString('| `data-widget-status-outlet` | Linked status controller updated when the widget toggles. |', $markdown);
        // Actions are opt-in from `@action` tags, each bounded to its own description.
        $this->assertStringContainsString('| `toggle` | Toggles the widget open state. |', $markdown);
    }

    public function testRenderAsHtmlIncludesStimulusControllerApiReference()
    {
        [$kit, $recipe] = $this->loadWidgetRecipe();

        $urlGenerator = new class implements PreviewUrlGenerator {
            public function generate(string $code, CodeOptions $options): ?string
            {
                return 'https://preview.test/render';
            }
        };

        $html = $this->renderer()->renderAsHtml($kit, $recipe, $urlGenerator)->html;

        // The Stimulus API reference must also render on the HTML path (tables, not just Markdown).
        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('data-controller', $html);
        $this->assertStringContainsString('data-widget-auto-close-value', $html);
        $this->assertStringContainsString('panel', $html);
        $this->assertStringContainsString('data-widget-open-class', $html);
        $this->assertStringContainsString('data-widget-status-outlet', $html);
        $this->assertStringContainsString('toggle', $html);
        $this->assertStringContainsString('Toggles the widget open state.', $html);
    }

    private function renderer(): RecipeDocRenderer
    {
        return new RecipeDocRenderer(self::getContainer()->get('twig'));
    }

    /**
     * @return array{0: Kit, 1: Recipe}
     */
    private function loadWidgetRecipe(): array
    {
        $kitFactory = new KitFactory(
            self::getContainer()->get('filesystem'),
            self::getContainer()->get('ux_toolkit.kit.kit_synchronizer'),
        );

        $kit = $kitFactory->createKitFromAbsolutePath(\dirname(__DIR__).'/Fixtures/kits/render-stimulus-doc');
        $recipe = $kit->getRecipe('widget');
        $this->assertNotNull($recipe);

        return [$kit, $recipe];
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
