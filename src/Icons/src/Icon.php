<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class Icon implements \Stringable
{
    /**
     * Transforms a valid icon ID into an icon name.
     *
     * @throws \InvalidArgumentException if the ID is not valid
     *
     * @see isValidId()
     */
    public static function idToName(string $id): string
    {
        if (!self::isValidId($id)) {
            throw new \InvalidArgumentException(sprintf('The id "%s" is not a valid id.', $id));
        }

        return str_replace('--', ':', $id);
    }

    /**
     * Transforms a valid icon name into an ID.
     *
     * @throws \InvalidArgumentException if the name is not valid
     *
     * @see isValidName()
     */
    public static function nameToId(string $name): string
    {
        if (!self::isValidName($name)) {
            throw new \InvalidArgumentException(sprintf('The name "%s" is not a valid name.', $name));
        }

        return str_replace(':', '--', $name);
    }

    /**
     * Returns whether the given string is a valid icon ID.
     *
     * An icon ID is a string that contains only lowercase letters, numbers, and hyphens.
     * It must be composed of slugs separated by double hyphens.
     *
     * @see https://regex101.com/r/mmvl5t/1
     */
    public static function isValidId(string $id): bool
    {
        return (bool) preg_match('#^([a-z0-9]+(-[a-z0-9]+)*)(--[a-z0-9]+(-[a-z0-9]+)*)*$#', $id);
    }

    /**
     * Returns whether the given string is a valid icon name.
     *
     * An icon name is a string that contains only lowercase letters, numbers, and hyphens.
     * It must be composed of slugs separated by colons.
     *
     * @see https://regex101.com/r/Gh2Z9s/1
     */
    public static function isValidName(string $name): bool
    {
        return (bool) preg_match('#^([a-z0-9]+(-[a-z0-9]+)*)(:[a-z0-9]+(-[a-z0-9]+)*)*$#', $name);
    }

    public static function fromFile(string $filename): self
    {
        if (!class_exists(\DOMDocument::class)) {
            throw new \LogicException('The "DOM" PHP extension is required to create icons from files.');
        }

        $svg = file_get_contents($filename) ?: throw new \RuntimeException(sprintf('The icon file "%s" could not be read.', $filename));

        $svgDoc = new \DOMDocument();
        $svgDoc->preserveWhiteSpace = false;

        try {
            $svgDoc->loadXML($svg);
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf('The icon file "%s" does not contain a valid SVG.', $filename), previous: $e);
        }

        $svgElements = $svgDoc->getElementsByTagName('svg');

        if (0 === $svgElements->length) {
            throw new \RuntimeException(sprintf('The icon file "%s" does not contain a valid SVG.', $filename));
        }

        if (1 !== $svgElements->length) {
            throw new \RuntimeException(sprintf('The icon file "%s" contains more than one SVG.', $filename));
        }

        $svgElement = $svgElements->item(0) ?? throw new \RuntimeException(sprintf('The icon file "%s" does not contain a valid SVG.', $filename));

        $innerSvg = '';

        foreach ($svgElement->childNodes as $node) {
            // Ignore comments and text nodes
            if ($node instanceof \DOMComment || $node instanceof \DOMText) {
                continue;
            }

            // Ignore script tags
            if ($node instanceof \DOMElement && 'script' === $node->nodeName) {
                continue;
            }

            $innerSvg .= $svgDoc->saveHTML($node);
        }

        if (!$innerSvg) {
            throw new \RuntimeException(sprintf('The icon file "%s" contains an empty SVG.', $filename));
        }

        $attributes = array_map(static fn (\DOMAttr $a) => $a->value, [...$svgElement->attributes]);

        return new self($innerSvg, $attributes);
    }

    public function __construct(
        private readonly string $innerSvg,
        private readonly array $attributes = [],
    ) {
    }

    public function toHtml(): string
    {
        $htmlAttributes = '';
        foreach ($this->attributes as $name => $value) {
            if (false === $value) {
                continue;
            }
            $htmlAttributes .= ' '.$name;
            if (true !== $value) {
                $value = htmlspecialchars($value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
                $htmlAttributes .= '="'.$value.'"';
            }
        }

        return '<svg'.$htmlAttributes.'>'.$this->innerSvg.'</svg>';
    }

    public function getInnerSvg(): string
    {
        return $this->innerSvg;
    }

    /**
     * @return array<string, string|bool>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param array<string, string|bool> $attributes
     */
    public function withAttributes(array $attributes): self
    {
        foreach ($attributes as $name => $value) {
            if (!\is_string($name)) {
                throw new \InvalidArgumentException(sprintf('Attribute names must be string, "%s" given.', get_debug_type($name)));
            }

            if (!ctype_alnum($name) && !str_contains($name, '-')) {
                throw new \InvalidArgumentException(sprintf('Invalid attribute name "%s".', $name));
            }

            if (!\is_string($value) && !\is_bool($value)) {
                throw new \InvalidArgumentException(sprintf('Invalid value type for attribute "%s". Boolean or string allowed, "%s" provided. ', $name, get_debug_type($value)));
            }
        }

        return new self($this->innerSvg, [...$this->attributes, ...$attributes]);
    }

    public function __toString(): string
    {
        return $this->toHtml();
    }

    public function __serialize(): array
    {
        return [$this->innerSvg, $this->attributes];
    }

    public function __unserialize(array $data): void
    {
        [$this->innerSvg, $this->attributes] = $data;
    }
}
