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
 * Keeps postcss' iteration contract: removing or inserting a node while `each()` runs shifts the running index.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
abstract class Container extends Node
{
    /**
     * @var list<Node>
     */
    public array $nodes = [];
    public string $after = "\n";

    /**
     * @var array<int, int>
     */
    private array $iterators = [];
    private int $lastIterator = 0;

    /**
     * @param list<Node> $nodes
     */
    public function __construct(array $nodes = [])
    {
        $this->append(...$nodes);
    }

    public function append(Node ...$nodes): void
    {
        foreach ($nodes as $node) {
            $node->parent?->removeChild($node);
            $node->parent = $this;
            $this->nodes[] = $node;
        }
    }

    public function insertBefore(Node $existing, Node $node): void
    {
        $node->parent?->removeChild($node);
        $index = $this->indexOf($existing);
        array_splice($this->nodes, $index, 0, [$node]);
        $node->parent = $this;
        foreach ($this->iterators as $id => $position) {
            if ($index <= $position) {
                $this->iterators[$id] = $position + 1;
            }
        }
    }

    public function removeChild(Node $node): void
    {
        $index = $this->indexOf($node);
        if (false === $index) {
            return;
        }

        array_splice($this->nodes, $index, 1);
        $node->parent = null;
        foreach ($this->iterators as $id => $position) {
            if ($position >= $index) {
                $this->iterators[$id] = $position - 1;
            }
        }
    }

    public function indexOf(Node $node): int|false
    {
        return array_search($node, $this->nodes, true);
    }

    /**
     * @param callable(Node, int): (bool|void|null) $callback returning false stops the iteration
     */
    public function each(callable $callback): bool
    {
        $id = ++$this->lastIterator;
        $this->iterators[$id] = 0;
        $result = true;
        while ($this->iterators[$id] < \count($this->nodes)) {
            $index = $this->iterators[$id];
            if (false === $callback($this->nodes[$index], $index)) {
                $result = false;
                break;
            }
            ++$this->iterators[$id];
        }
        unset($this->iterators[$id]);

        return $result;
    }

    /**
     * @param callable(Node, int): (bool|void|null) $callback returning false stops the walk
     */
    public function walk(callable $callback): bool
    {
        return $this->each(static function (Node $child, int $index) use ($callback) {
            $result = $callback($child, $index);
            if (false !== $result && $child instanceof self) {
                $result = $child->walk($callback);
            }

            return $result;
        });
    }

    /**
     * @param callable(Rule): (bool|void|null) $callback
     */
    public function walkRules(callable $callback): void
    {
        $this->walk(static fn (Node $node) => $node instanceof Rule ? $callback($node) : null);
    }

    public function __clone()
    {
        parent::__clone();

        $nodes = $this->nodes;
        $this->nodes = [];
        $this->iterators = [];
        foreach ($nodes as $node) {
            $copy = clone $node;
            $copy->parent = $this;
            $this->nodes[] = $copy;
        }
    }

    protected function childrenToCompactString(): string
    {
        $css = '';
        $previous = null;
        foreach ($this->nodes as $node) {
            if ($previous instanceof Declaration) {
                $css .= ';';
            }
            $css .= $node->toCompactString();
            $previous = $node;
        }

        return $css;
    }

    protected function childrenToString(): string
    {
        $css = '';
        foreach ($this->nodes as $node) {
            $css .= $node->before.$node->toString();
        }

        return $css;
    }
}
