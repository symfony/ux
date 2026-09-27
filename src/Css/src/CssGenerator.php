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
     * @param iterable<array<array-key, mixed>> $styles the hashes given to css()
     *
     * @return list<string> the classes the stylesheet of these hashes has rules for
     */
    public function classNames(iterable $styles): array
    {
        $classes = [];
        foreach ($this->engine->decoder()->decode($this->entries($styles)) as $style) {
            $classes[$style->htmlClass] = true;
        }

        return array_map(strval(...), array_keys($classes));
    }

    /**
     * @param iterable<array<array-key, mixed>> $styles the hashes given to css()
     */
    public function generate(iterable $styles, bool $compact = false): string
    {
        $stylesheet = $this->engine->stylesheet();
        $atomicStyles = $this->engine->decoder()->decode($this->entries($styles));
        $root = $stylesheet->build($atomicStyles, $this->engine->tokenCss()->nodes());
        if ([] === $root->nodes) {
            return '';
        }

        if ($compact) {
            return '@layer tokens,utilities;'.$root->toCompactString();
        }

        return "@layer tokens, utilities;\n\n".$root->toString()."\n";
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
