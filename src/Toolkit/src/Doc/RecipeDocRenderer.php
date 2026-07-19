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
use League\CommonMark\MarkdownConverter;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Assert;
use Symfony\UX\Toolkit\Component\ComponentDoc;
use Symfony\UX\Toolkit\Component\ComponentDocParser;
use Symfony\UX\Toolkit\Installer\PoolResolver;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Markdown\Extension\Alert\AlertExtension;
use Symfony\UX\Toolkit\Markdown\Extension\CodePreview\CodePreviewExtension;
use Symfony\UX\Toolkit\Markdown\Extension\Example\ExampleExtension;
use Symfony\UX\Toolkit\Markdown\Extension\Example\ExampleResolver;
use Symfony\UX\Toolkit\Markdown\Extension\FencedCodePreview\FencedCodePreviewExtension;
use Symfony\UX\Toolkit\Markdown\Extension\Popover\PopoverExtension;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\TabsExtension;
use Symfony\UX\Toolkit\Markdown\PreviewUrlGenerator;
use Symfony\UX\Toolkit\Recipe\Recipe;

/**
 * Renders a recipe's documentation as HTML (through league/commonmark and the Toolkit's extensions)
 * or as portable Markdown.
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
        return $this->resolveExamplesToFences($recipe, $this->renderTemplate($kit, $recipe, 'markdown'));
    }

    public function renderAsHtml(Kit $kit, Recipe $recipe, PreviewUrlGenerator $previewUrlGenerator): string
    {
        Assert::commonMarkAvailable();

        $environment = new Environment();
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new AlertExtension($this->twig));
        $environment->addExtension(new TabsExtension($this->twig));
        $environment->addExtension(new PopoverExtension($this->twig));
        $environment->addExtension(new CodePreviewExtension($this->twig, $previewUrlGenerator));
        $environment->addExtension(new FencedCodePreviewExtension());
        $environment->addExtension(new ExampleExtension($recipe));

        return (string) new MarkdownConverter($environment)->convert($this->renderTemplate($kit, $recipe, 'html'));
    }

    private function renderTemplate(Kit $kit, Recipe $recipe, string $format): string
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

        return $this->twig->render('@UXToolkit/doc/recipe.md.twig', [
            'kit' => $kit,
            'kit_id' => basename($kit->absolutePath),
            'recipe' => $recipe,
            'files' => $files,
            'php_package_dependencies' => array_values($pool->getPhpPackageDependencies()),
            'npm_package_dependencies' => array_values($pool->getNpmPackageDependencies()),
            'importmap_package_dependencies' => array_values($pool->getImportmapPackageDependencies()),
            'api_reference' => $this->extractApiReference($recipe),
            'format' => $format,
        ]);
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

    /**
     * Replaces every `::: example <Name>` line with the example's code as a fenced block. Used for the
     * portable Markdown output, where interactive directives are not wanted.
     */
    private function resolveExamplesToFences(Recipe $recipe, string $markdown): string
    {
        return preg_replace_callback('/^::: example (?<rest>.+)$/m', static function (array $matches) use ($recipe): string {
            [$name] = ExampleResolver::parse($matches['rest']);
            $code = ExampleResolver::readCode($recipe, $name);
            $fence = str_contains($code, '```') ? '````' : '```';

            return $fence.'twig'."\n".$code."\n".$fence;
        }, $markdown);
    }
}
