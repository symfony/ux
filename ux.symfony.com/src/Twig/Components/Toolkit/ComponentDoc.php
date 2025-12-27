<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Toolkit;

use App\Enum\ToolkitKitId;
use App\Service\Toolkit\ToolkitService;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class ComponentDoc
{
    public ToolkitKitId $kitId;
    public Recipe $component;

    /**
     * @see https://regex101.com/r/8L2pPy/1
     */
    private const RE_CODE_BLOCK = '/```(?P<language>[a-z]+?)\s*(?P<options>\{.+?\})?\n(?P<code>.+?)```/s';

    public function __construct(
        private readonly ToolkitService $toolkitService,
        private readonly \Twig\Environment $twig,
    ) {
    }

    public function getMarkdownContent(): string
    {
        $kit = $this->toolkitService->getKit($this->kitId);
        $pool = $this->toolkitService->resolveRecipePool($kit, $this->component);
        $examples = $this->toolkitService->extractRecipeExamples($this->component);
        $apiReference = $this->toolkitService->extractRecipeApiReference($this->component);

        $files = [];
        foreach ($pool->getFiles() as $recipeFullPath => $recipeFiles) {
            foreach ($recipeFiles as $recipeFile) {
                $recipeFileSourcePath = Path::join($recipeFullPath, $recipeFile->sourceRelativePathName);
                $files[] = [
                    'path_name' => $recipeFile->sourceRelativePathName,
                    'content' => file_get_contents($recipeFileSourcePath),
                    'language' => pathinfo($recipeFileSourcePath, \PATHINFO_EXTENSION),
                ];
            }
        }

        return $this->twig->render('toolkit/_component.md.twig', [
            'kit_id' => $this->kitId,
            'component' => $this->component,
            'files' => $files,
            'php_package_dependencies' => $pool->getPhpPackageDependencies(),
            'npm_package_dependencies' => $pool->getNpmPackageDependencies(),
            'importmap_package_dependencies' => $pool->getImportmapPackageDependencies(),
            'usage' => current($examples),
            'examples' => $this->formatExamples($examples),
            'api_reference' => $apiReference,
        ]);
    }

    /**
     * @param array<string, string> $examples
     *
     * @return array<string, string>
     */
    private function formatExamples(array $examples): array
    {
        foreach ($examples as $title => $example) {
            $examples[$title] = preg_replace_callback(self::RE_CODE_BLOCK, function (array $matches) {
                $language = $matches['language'];
                $options = json_validate($matches['options'] ?? '') ? json_decode($matches['options'], true) : [];
                $preview = $options['preview'] ?? false;
                $code = trim($matches['code']);

                if ($preview) {
                    return $this->twig->render('toolkit/recipe/_previewable_code_block.md.twig', [
                        'code' => $code,
                        'language' => $language,
                        'options' => $options + ['kit' => $this->kitId->value],
                    ]);
                }

                return $this->twig->render('toolkit/recipe/_code_block.md.twig', [
                    'code' => $code,
                    'language' => $language,
                    'options' => $options,
                ]);
            }, $example);
        }

        return $examples;
    }
}
