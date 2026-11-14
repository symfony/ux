<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css;

use Symfony\UX\Css\Engine\AtomicStyle;
use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Engine\StyleEntry;

/**
 * Writes the CSS of every token and of the given styles: one rule per style, however many templates use it.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class CssGenerator
{
    public function __construct(
        private readonly Engine $engine,
    ) {
    }

    /**
     * The stylesheet of these hashes, and the classes it has rules for.
     *
     * @param iterable<array<array-key, mixed>> $styles the hashes given to css()
     *
     * @return array{string, list<string>}
     */
    public function generateWithClassNames(iterable $styles, bool $compact = false): array
    {
        $atomicStyles = $this->engine->decoder()->decode($this->entries($styles));

        $classes = [];
        foreach ($atomicStyles as $style) {
            $classes[$style->htmlClass] = true;
        }

        return [$this->render($atomicStyles, $compact), array_map(strval(...), array_keys($classes))];
    }

    /**
     * @param iterable<array<array-key, mixed>> $styles the hashes given to css()
     */
    public function generate(iterable $styles, bool $compact = false): string
    {
        return $this->render($this->engine->decoder()->decode($this->entries($styles)), $compact);
    }

    /**
     * @param list<AtomicStyle> $atomicStyles
     */
    private function render(array $atomicStyles, bool $compact): string
    {
        $stylesheet = $this->engine->stylesheet();
        $preflight = $this->engine->hasPreflight();
        $reset = $preflight ? $this->engine->resetCss()->nodes() : [];
        $root = $stylesheet->build($atomicStyles, $this->engine->tokenCss()->nodes(), $reset);
        if ([] === $root->nodes) {
            return '';
        }

        $layers = $preflight ? ['reset', 'tokens', 'utilities'] : ['tokens', 'utilities'];
        if ($compact) {
            return '@layer '.implode(',', $layers).';'.$root->toCompactString();
        }

        return '@layer '.implode(', ', $layers).";\n\n".$root->toString()."\n";
    }

    /**
     * @param iterable<array<array-key, mixed>> $styles
     *
     * @return list<StyleEntry>
     */
    private function entries(iterable $styles): array
    {
        $entries = [];
        foreach ($styles as $style) {
            foreach ($this->engine->encoder()->encode($style) as $entry) {
                $entries[$entry->hash()] ??= $entry;
            }
        }

        return array_values($entries);
    }
}
