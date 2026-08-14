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
use Symfony\Component\Finder\Finder;
use Symfony\UX\Toolkit\File;
use Symfony\UX\Toolkit\Markdown\CodeOptions;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Recipe
{
    /**
     * @var list<File>|null
     */
    private ?array $files = null;

    /**
     * @param non-empty-string $name
     * @param non-empty-string $absolutePath
     */
    public function __construct(
        public readonly string $name,
        public readonly string $absolutePath,
        public readonly RecipeManifest $manifest,
        public readonly ?string $doc = null,
    ) {
        if (!Path::isAbsolute($this->absolutePath)) {
            throw new \InvalidArgumentException(\sprintf('Kit path "%s" is not absolute.', $this->absolutePath));
        }
    }

    /**
     * The recipe description, read from the first paragraph of its README (right after the title
     * heading), or null when the recipe ships no README or opens with something other than a
     * title + description (e.g. a directive). Useful for catalogs and llms.txt, where a plain-text
     * blurb is needed without rendering the whole document.
     */
    public function getDescription(): ?string
    {
        if (null === $this->doc) {
            return null;
        }

        $blocks = preg_split('/\R\s*\R/', trim($this->doc), 3);
        if (\count($blocks) < 2 || !str_starts_with(ltrim($blocks[0]), '# ')) {
            return null;
        }

        $description = trim($blocks[1]);
        if ('' === $description || str_starts_with($description, '#') || str_starts_with($description, ':::')) {
            return null;
        }

        return $description;
    }

    /**
     * The recipe's runnable examples: fenced code blocks whose info string carries `{"preview": true, ...}`.
     * Static blocks (no `preview`) — e.g. the Usage signature — are excluded. Regex over the raw README so
     * Toolkit core needs no Markdown parser; example code must therefore not contain a nested ``` fence.
     *
     * @return list<array{language: string, code: string, options: CodeOptions}>
     */
    public function getExamples(): array
    {
        if (null === $this->doc) {
            return [];
        }

        if (!preg_match_all('/^```(?<language>\S+)\h+(?<json>\{.*?\})\h*$\R(?<code>.*?)\R```\h*$/ms', $this->doc, $matches, \PREG_SET_ORDER)) {
            return [];
        }

        $examples = [];
        foreach ($matches as $match) {
            $options = CodeOptions::fromInfoJson($match['json']);
            if (null === $options) {
                continue;
            }

            $examples[] = [
                'language' => $match['language'],
                'code' => $match['code'],
                'options' => $options,
            ];
        }

        return $examples;
    }

    /**
     * @return iterable<File>
     */
    public function getFiles(): iterable
    {
        // This used to be a generator, so every consumer re-walked the recipe
        // directories from disk. Several of them iterate the same recipe more
        // than once while rendering or installing it.
        if (null !== $this->files) {
            return $this->files;
        }

        $files = [];
        foreach ($this->manifest->copyFiles as $source => $destination) {
            $finder = new Finder()->in(Path::join($this->absolutePath, $source))->sortByName()->files();

            foreach ($finder as $file) {
                $files[] = new File(Path::join($source, $file->getRelativePathname()), Path::join($destination, $file->getRelativePathname()));
            }
        }

        return $this->files = $files;
    }
}
