<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Doc;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Node\NodeIterator;
use League\CommonMark\Renderer\HtmlRenderer;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Assert;
use Symfony\UX\Toolkit\Component\ComponentDoc;
use Symfony\UX\Toolkit\Component\ComponentDocParser;
use Symfony\UX\Toolkit\Installer\PoolResolver;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Markdown\Extension\Alert\AlertExtension;
use Symfony\UX\Toolkit\Markdown\Extension\CodePreview\CodePreviewExtension;
use Symfony\UX\Toolkit\Markdown\Extension\FencedCodePreview\FencedCodePreviewExtension;
use Symfony\UX\Toolkit\Markdown\Extension\Popover\PopoverExtension;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\TabsExtension;
use Symfony\UX\Toolkit\Markdown\PreviewUrlGenerator;
use Symfony\UX\Toolkit\Recipe\Recipe;

/**
 * Renders a recipe's documentation as HTML (through league/commonmark and the Toolkit's extensions)
 * or as portable Markdown.
 *
 * The body is the recipe's `README.md` when present, otherwise a default layout. The author controls the
 * layout: examples are inline fenced code blocks flagged `{"preview": true}` (live previews), and the
 * generated pieces drop in via `::: installation` (install steps) and `::: api-reference` (props/blocks tables).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class RecipeDocRenderer
{
    private readonly ComponentDocParser $componentDocParser;

    public function __construct(
        private readonly \Twig\Environment $twig,
        ?ComponentDocParser $componentDocParser = null,
    ) {
        $this->componentDocParser = $componentDocParser ?? new ComponentDocParser($twig);
    }

    public function renderAsMarkdown(Kit $kit, Recipe $recipe): string
    {
        // Portable Markdown carries no render metadata: strip any `{json}` info string from fenced blocks.
        return preg_replace('/^(```\S+)\h+\{.*\}\h*$/m', '$1', $this->renderBody($kit, $recipe, 'markdown'));
    }

    /**
     * A host can inject its own CommonMark environment; the recipe-specific extensions are always added
     * on top. When none is given, a standalone default is built with the Toolkit's own Markdown extensions.
     */
    public function renderAsHtml(Kit $kit, Recipe $recipe, PreviewUrlGenerator $previewUrlGenerator, ?Environment $environment = null): RenderedRecipeDoc
    {
        Assert::commonMarkAvailable();

        if (null === $environment) {
            // id-only anchors so the table of contents has ids to link to.
            $environment = new Environment(['heading_permalink' => ['insert' => 'none', 'apply_id_to_heading' => true]]);
            $environment->addExtension(new CommonMarkCoreExtension());
            $environment->addExtension(new TableExtension());
            $environment->addExtension(new HeadingPermalinkExtension());
            $environment->addExtension(new AlertExtension($this->twig));
            $environment->addExtension(new TabsExtension($this->twig));
            $environment->addExtension(new PopoverExtension($this->twig));
        }

        $environment->addExtension(new CodePreviewExtension($this->twig, $previewUrlGenerator));
        $environment->addExtension(new FencedCodePreviewExtension());

        $rendered = new MarkdownConverter($environment)->convert($this->renderBody($kit, $recipe, 'html'));

        return new RenderedRecipeDoc((string) $rendered, $this->extractTableOfContents($rendered->getDocument(), $environment));
    }

    /**
     * The `<h2>`/`<h3>` nodes carry the `id` stamped by the heading-anchor extension; their inner
     * HTML is kept (e.g. `<code>`).
     *
     * @return list<array{level: int, title: string, id: string}>
     */
    private function extractTableOfContents(Document $document, Environment $environment): array
    {
        $renderer = new HtmlRenderer($environment);

        $items = [];
        foreach ($document->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if (!$node instanceof Heading || !\in_array($node->getLevel(), [2, 3], true)) {
                continue;
            }

            $id = $node->data->get('attributes/id', null);
            if (null === $id) {
                continue;
            }

            $items[] = [
                'level' => $node->getLevel(),
                'title' => (string) $renderer->renderNodes($node->children()),
                'id' => (string) $id,
            ];
        }

        return $items;
    }

    private function renderBody(Kit $kit, Recipe $recipe, string $format): string
    {
        $context = $this->buildContext($kit, $recipe, $format);

        $body = $recipe->doc ?? $this->defaultBody($recipe, [] !== $context['api_reference']);

        return preg_replace_callback('/^::: (?<directive>installation|api-reference)\s*$/m', fn (array $matches): string => trim($this->twig->render(\sprintf('@UXToolkit/doc/_%s.md.twig', str_replace('-', '_', $matches['directive'])), $context)), $body);
    }

    private function defaultBody(Recipe $recipe, bool $hasApiReference): string
    {
        $body = \sprintf("# %s\n\n## Installation\n\n::: installation\n", $recipe->manifest->name);
        if ($hasApiReference) {
            $body .= "\n## API Reference\n\n::: api-reference\n";
        }

        return $body;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildContext(Kit $kit, Recipe $recipe, string $format): array
    {
        $pool = new PoolResolver()->resolveForRecipe($kit, $recipe);

        $files = [];
        foreach ($pool->getFiles() as $recipeAbsolutePath => $recipeFiles) {
            foreach ($recipeFiles as $file) {
                $source = Path::join($recipeAbsolutePath, $file->sourceRelativePathName);
                $files[] = [
                    'path_name' => $file->sourceRelativePathName,
                    'content' => is_file($source) ? file_get_contents($source) : '',
                    'language' => pathinfo($source, \PATHINFO_EXTENSION),
                ];
            }
        }

        return [
            'kit' => $kit,
            'kit_id' => basename($kit->absolutePath),
            'recipe' => $recipe,
            'files' => $files,
            'php_package_dependencies' => array_values($pool->getPhpPackageDependencies()),
            'npm_package_dependencies' => array_values($pool->getNpmPackageDependencies()),
            'importmap_package_dependencies' => array_values($pool->getImportmapPackageDependencies()),
            'api_reference' => $this->extractApiReference($recipe),
            'format' => $format,
        ];
    }

    /**
     * @return array<string, ComponentDoc>
     */
    private function extractApiReference(Recipe $recipe): array
    {
        $apiReference = [];

        foreach ($recipe->getFiles() as $file) {
            $source = $file->sourceRelativePathName;
            if (!str_ends_with($source, '.html.twig') || !str_starts_with($source, 'templates/components/')) {
                continue;
            }

            $filePath = Path::join($recipe->absolutePath, $source);
            if (!is_file($filePath)) {
                continue;
            }

            $componentDoc = $this->componentDocParser->parse(file_get_contents($filePath));
            if ([] === $componentDoc->props && [] === $componentDoc->blocks) {
                continue;
            }

            $componentName = str_replace('/', ':', substr($source, \strlen('templates/components/'), -\strlen('.html.twig')));
            $apiReference[$componentName] = $componentDoc;
        }

        return $apiReference;
    }
}
