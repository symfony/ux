<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Dumper;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Css\CssGenerator;
use Symfony\UX\Css\Engine\StaticCss;
use Symfony\UX\Css\Twig\CssSyntaxError;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

/**
 * Writes the stylesheet of every template, from an index of the style hashes of each one.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class StylesheetDumper
{
    /**
     * @param \Closure(): iterable<string> $templates   the names of every template of the application, listed again on each call
     * @param list<array<string, mixed>>   $staticRules the `css` rules of the static_css config
     * @param string|null                  $classesFile where to list, in debug, the classes that have CSS
     * @param string                       $configHash  changes with the config, which the compiled templates depend on
     */
    public function __construct(
        private readonly TemplateScanner $scanner,
        private readonly CssGenerator $generator,
        private readonly Environment $twig,
        private readonly \Closure $templates,
        private readonly string $indexFile,
        private readonly string $stylesheetFile,
        private readonly bool $debug,
        private readonly ?StaticCss $staticCss = null,
        private readonly array $staticRules = [],
        private readonly ?string $classesFile = null,
        private readonly string $configHash = '',
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    /**
     * Reads again the templates changed since the last call, and rewrites the stylesheet when their styles changed.
     *
     * A template that no longer parses keeps the styles it had.
     *
     * @return list<string> the templates read again
     */
    public function update(): array
    {
        $index = $this->loadIndex();
        $names = $this->templateNames();
        $loader = $this->twig->getLoader();

        $removed = array_diff_key($index, array_flip($names));
        $index = array_diff_key($index, $removed);
        $changed = [] !== $removed;

        // a long-running process would otherwise see the state of a file as it was the first time
        clearstatcache();

        $readAgain = [];
        foreach ($names as $name) {
            $entry = $index[$name] ?? null;
            if (null !== $entry && '' !== $entry['path'] && !is_file($entry['path'])) {
                unset($index[$name]);
                $changed = true;
                continue;
            }

            $time = time();
            try {
                if (null !== $entry && $this->isFresh($name, $entry, $loader)) {
                    continue;
                }
                $scanned = $this->scanner->scan($name);
            } catch (\Throwable) {
                continue;
            }

            $index[$name] = ['time' => $time, ...$scanned];
            $readAgain[] = $name;
            $changed = true;
        }

        if (!$changed && is_file($this->stylesheetFile)) {
            return [];
        }

        $this->write($index);

        return $readAgain;
    }

    /**
     * Reads every template and writes the stylesheet, compact outside of debug.
     *
     * @throws CssSyntaxError outside of debug, when a css() call cannot be compiled
     */
    public function dump(): void
    {
        // forgets the templates compiled for another config
        $this->loadIndex();

        $index = [];
        foreach ($this->templateNames() as $name) {
            $time = time();
            try {
                $scanned = $this->scanner->scan($name);
            } catch (\Throwable $e) {
                if (!$this->debug && $e instanceof CssSyntaxError) {
                    throw $e;
                }
                continue;
            }

            $index[$name] = ['time' => $time, ...$scanned];
        }

        $this->write($index);
    }

    /**
     * @param array{time: int, path: string, styles: list<array<array-key, mixed>>} $entry
     */
    private function isFresh(string $name, array $entry, LoaderInterface $loader): bool
    {
        // the loader keeps the path of the templates it found, which a long-running process cannot trust
        if ('' !== $entry['path']) {
            return filemtime($entry['path']) < $entry['time'];
        }

        return $loader->isFresh($name, $entry['time']);
    }

    /**
     * @param array<string, array{time: int, path: string, styles: list<array<array-key, mixed>>}> $index
     */
    private function write(array $index): void
    {
        $content = ['config' => $this->configHash, 'templates' => $index];
        $this->filesystem->dumpFile($this->indexFile, json_encode($content, \JSON_THROW_ON_ERROR));

        $styles = null !== $this->staticCss ? $this->staticCss->styles($this->staticRules) : [];
        foreach ($index as $entry) {
            array_push($styles, ...$entry['styles']);
        }

        if ($this->debug && null !== $this->classesFile) {
            $classes = json_encode($this->generator->classNames($styles), \JSON_THROW_ON_ERROR);
            $this->filesystem->dumpFile($this->classesFile, $classes);
        }

        $css = $this->generator->generate($styles, !$this->debug);
        if (!is_file($this->stylesheetFile) || file_get_contents($this->stylesheetFile) !== $css) {
            $this->filesystem->dumpFile($this->stylesheetFile, $css);
        }
    }

    /**
     * @return array<string, array{time: int, path: string, styles: list<array<array-key, mixed>>}> the templates of the index, or none when it was written for another config
     */
    private function loadIndex(): array
    {
        if (!is_file($this->indexFile)) {
            return [];
        }

        $index = json_decode(file_get_contents($this->indexFile), true, flags: \JSON_THROW_ON_ERROR);
        $templates = $index['templates'] ?? [];
        if (($index['config'] ?? null) === $this->configHash) {
            return $templates;
        }

        if ($this->debug) {
            $this->forgetCompiledTemplates($templates);
        }

        return [];
    }

    /**
     * Compiled templates keep the classes of the config they were compiled with.
     *
     * @param array<string, array{time: int, path: string, styles: list<array<array-key, mixed>>}> $templates
     */
    private function forgetCompiledTemplates(array $templates): void
    {
        foreach ($templates as $name => $entry) {
            if ([] === $entry['styles']) {
                continue;
            }

            try {
                $this->twig->removeCache($name);
            } catch (\LogicException) {
                return;
            }
        }
    }

    /**
     * @return list<string>
     */
    private function templateNames(): array
    {
        $names = [];
        foreach (($this->templates)() as $name) {
            $names[$name] = true;
        }

        return array_keys($names);
    }
}
