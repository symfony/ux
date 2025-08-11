<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Kit;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\UX\Toolkit\Recipe\RecipeSynchronizer;

/**
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class KitSynchronizer
{
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly RecipeSynchronizer $recipeSynchronizer,
    ) {
    }

    public function synchronize(Kit $kit): void
    {
        if ($this->filesystem->exists($installMd = Path::join($kit->absolutePath, 'INSTALL.md'))) {
            $kit->installAsMarkdown = file_get_contents($installMd);
        }

        $this->synchronizeRecipes($kit);
    }

    private function synchronizeRecipes(Kit $kit): void
    {
        $finder = (new Finder())
            ->in($kit->absolutePath)
            ->files()
            ->depth('== 1')
            ->sortByName()
            ->name('manifest.json');

        if (!$finder->hasResults()) {
            throw new \RuntimeException(\sprintf('No recipes found at "%s".', $kit->absolutePath));
        }

        foreach ($finder as $manifestFile) {
            $this->recipeSynchronizer->synchronizeRecipe($kit, $manifestFile);
        }
    }
}
