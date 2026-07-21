<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Recipe;

use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\UX\Toolkit\Kit\Kit;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class RecipeSynchronizer
{
    public function synchronizeRecipe(Kit $kit, SplFileInfo $manifestFile): void
    {
        try {
            $manifest = RecipeManifest::fromJson($manifestFile->getContents());
        } catch (\JsonException|\InvalidArgumentException $e) {
            throw new \RuntimeException(\sprintf('Unable to parse manifest file "%s": "%s"', $manifestFile->getPathname(), $e->getMessage()), previous: $e);
        }

        $docFile = Path::join($manifestFile->getPath(), 'README.md');

        $recipe = new Recipe(
            name: $manifestFile->getPathInfo()->getBasename(),
            absolutePath: $manifestFile->getPath(),
            manifest: $manifest,
            doc: is_file($docFile) ? file_get_contents($docFile) : null,
        );

        $kit->addRecipe($recipe);
    }
}
