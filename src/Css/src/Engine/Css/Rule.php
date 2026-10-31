<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Engine\Css;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Rule extends Container
{
    /**
     * @param list<Node> $nodes
     */
    public function __construct(
        public string $selector,
        array $nodes = [],
    ) {
        parent::__construct($nodes);
    }

    /**
     * Port of postcss' `list.comma()`: splits on commas outside of parentheses and quotes.
     *
     * @return list<string>
     */
    public function selectors(): array
    {
        $selectors = [];
        $current = '';
        $depth = 0;
        $quote = null;
        $escaped = false;
        foreach (preg_split('//u', $this->selector, -1, \PREG_SPLIT_NO_EMPTY) as $char) {
            if ($escaped) {
                $escaped = false;
            } elseif ('\\' === $char) {
                $escaped = true;
            } elseif (null !== $quote) {
                if ($char === $quote) {
                    $quote = null;
                }
            } elseif ('"' === $char || "'" === $char) {
                $quote = $char;
            } elseif ('(' === $char) {
                ++$depth;
            } elseif (')' === $char && $depth > 0) {
                --$depth;
            } elseif (',' === $char && 0 === $depth) {
                $selectors[] = trim($current);
                $current = '';
                continue;
            }
            $current .= $char;
        }
        $selectors[] = trim($current);

        return array_values(array_filter($selectors, static fn (string $selector): bool => '' !== $selector));
    }

    /**
     * @return list<Declaration>
     */
    public function declarations(): array
    {
        return array_values(array_filter($this->nodes, static fn (Node $node): bool => $node instanceof Declaration));
    }

    public function toString(): string
    {
        return $this->selector.' {'.$this->childrenToString().$this->after.'}';
    }

    public function toCompactString(): string
    {
        return implode(',', $this->selectors()).'{'.$this->childrenToCompactString().'}';
    }
}
