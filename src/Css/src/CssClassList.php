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

use Twig\Error\RuntimeError;
use Twig\Extra\Html\HtmlAttr\AttributeValueInterface;
use Twig\Extra\Html\HtmlAttr\MergeableInterface;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class CssClassList implements \Stringable, AttributeValueInterface, MergeableInterface, \JsonSerializable
{
    /**
     * @var array<string, string> each class mapped to its merge key
     */
    private array $classes = [];

    /**
     * @var list<array{string, ?string}>|null each class with its merge key, or null for a raw string; only built once the list is merged
     */
    private ?array $items = null;

    /**
     * @param array<string, string> $classes each class mapped to its merge key
     */
    public function __construct(array $classes = [])
    {
        $this->classes = $classes;
    }

    public function __toString(): string
    {
        return $this->getValue() ?? '';
    }

    public function getValue(): ?string
    {
        if (null !== $this->items) {
            return $this->items ? implode(' ', array_column($this->items, 0)) : null;
        }

        return $this->classes ? implode(' ', array_keys($this->classes)) : null;
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }

    public function mergeInto(mixed $previous): self
    {
        if ($previous instanceof self) {
            $items = $this->items();
            $overridden = array_flip(array_filter(array_column($items, 1)));
            $kept = array_filter($previous->items(), static fn (array $item) => null === $item[1] || !isset($overridden[$item[1]]));

            return $this->withItems([...$kept, ...$items]);
        }

        $raw = $this->toRawItems($previous, 'merged into');

        return $this->withItems([...$raw, ...$this->items()]);
    }

    public function appendFrom(mixed $newValue): self
    {
        $raw = $this->toRawItems($newValue, 'appended to');

        return $this->withItems([...$this->items(), ...$raw]);
    }

    /**
     * @return list<array{string, ?string}>
     */
    private function items(): array
    {
        if (null !== $this->items) {
            return $this->items;
        }

        $items = [];
        foreach ($this->classes as $class => $key) {
            $items[] = [(string) $class, $key];
        }

        return $items;
    }

    /**
     * @return list<array{string, null}>
     */
    private function toRawItems(mixed $value, string $action): array
    {
        if (null === $value || false === $value || '' === $value) {
            return [];
        }

        if (!\is_string($value) && !$value instanceof \Stringable) {
            throw new RuntimeError(\sprintf('A CssClassList can only be %s a string, a Stringable or another CssClassList, "%s" given.', $action, get_debug_type($value)));
        }

        $string = (string) $value;

        return '' === $string ? [] : [[$string, null]];
    }

    /**
     * @param list<array{string, ?string}> $items
     */
    private function withItems(array $items): self
    {
        $list = new self();
        $list->items = $items;

        return $list;
    }
}
