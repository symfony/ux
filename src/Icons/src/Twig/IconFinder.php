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
    public function __construct(private Environment $twig)
    {
    }

    /**
     * @return string[]
     */
    public function icons(): array
    {
        $found = [];

        foreach ($this->files($this->twig->getLoader()) as $file) {
            $contents = file_get_contents($file);

            if (preg_match_all('#ux_icon\(["\']([\w:-]+)["\']#', $contents, $matches)) {
                $found[] = $matches[1];
            }

            if (preg_match_all('#name=["\']([\w:-]+)["\']#', $contents, $matches)) {
                $found[] = $matches[1];
            }
        }

        return array_unique(array_merge(...$found));
    }

    /**
     * @return string[]
     */
    private function files(LoaderInterface $loader): iterable
    {
        $files = [];

        if ($loader instanceof FilesystemLoader) {
            foreach ($loader->getNamespaces() as $namespace) {
                foreach ($loader->getPaths($namespace) as $path) {
                    foreach ((new Finder())->files()->in($path)->name('*.twig') as $file) {
                        $file = (string) $file;
                        if (!\in_array($file, $files, true)) {
                            yield $file;
                        }

                        $files[] = $file;
                    }
                }
            }
        }

        if ($loader instanceof ChainLoader) {
            foreach ($loader->getLoaders() as $subLoader) {
                yield from $this->files($subLoader);
            }
        }
    }
}
