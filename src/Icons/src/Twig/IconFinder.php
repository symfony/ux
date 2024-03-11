<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Twig;

use Symfony\Component\Finder\Finder;
use Twig\Environment;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class IconFinder
{
    public function __construct(
        private Environment $twig,
        private string $iconDirectory,
    ) {
    }

    /**
     * @return string[]
     */
    public function icons(): array
    {
        $found = [];

        // https://regex101.com/r/WGa4iF/1
        $token = '[a-z0-9]+(?:-[a-z0-9]+)*';
        $pattern = "#(?:'$token:$token')|(?:\"$token:$token\")#i";

        // Extract icon names from strings in app templates
        foreach ($this->templateFiles($this->twig->getLoader()) as $file) {
            $contents = file_get_contents($file);
            if (preg_match_all($pattern, $contents, $matches)) {
                $found[] = array_map(fn ($res) => trim($res, '"\''), $matches[0]);
            }
        }
        $found = array_merge(...$found);

        // Extract prefix-less SVG files from the root of the icon directory
        if (is_dir($this->iconDirectory)) {
            $icons = (new Finder())->files()->in($this->iconDirectory)->depth(0)->name('*.svg');
            foreach ($icons as $icon) {
                $found[] = $icon->getBasename('.svg');
            }
        }

        return array_unique($found);
    }

    /**
     * @return string[]
     */
    private function templateFiles(LoaderInterface $loader): iterable
    {
        if ($loader instanceof FilesystemLoader) {
            $paths = [];
            foreach ($loader->getNamespaces() as $namespace) {
                $paths = [...$paths, ...$loader->getPaths($namespace)];
            }
            foreach ((new Finder())->files()->in($paths)->name('*.twig') as $file) {
                yield (string) $file;
            }
        }

        if ($loader instanceof ChainLoader) {
            foreach ($loader->getLoaders() as $subLoader) {
                yield from $this->templateFiles($subLoader);
            }
        }
    }
}
