<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

use Symfony\UX\StimulusBundle\Dto\StimulusAttributes;
use Twig\Extra\Html\HtmlAttr\AttributeValueInterface;
use Twig\Extra\Html\HtmlAttr\MergeableInterface;
use Twig\Extra\Html\HtmlExtension;
use Twig\Runtime\EscaperRuntime;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @immutable
 */
final class ComponentAttributes implements \Stringable, \IteratorAggregate, \Countable
{
    private const NESTED_REGEX = '#^([\w-]+):(.+)$#';
    private const ALPINE_REGEX = '#^x-([a-z]+):[^:]+$#';
    private const VUE_REGEX = '#^v-([a-z]+):[^:]+$#';

    /** @var array<string,true> */
    private array $rendered = [];

    /**
     * @param array<string, string|bool|int|float|null|iterable|\Stringable|\BackedEnum|AttributeValueInterface> $attributes
     */
    public function __construct(
        private array $attributes,
        private readonly EscaperRuntime $escaper,
    ) {
    }

    public function __toString(): string
    {
        $attributes = '';
        foreach ($this->attributes as $key => $value) {
            if (isset($this->rendered[$key])) {
                continue;
            }

            if (
                str_contains($key, ':')
                && preg_match(self::NESTED_REGEX, $key)
                && !preg_match(self::ALPINE_REGEX, $key)
                && !preg_match(self::VUE_REGEX, $key)
            ) {
                continue;
            }

            if (null === $value = HtmlExtension::htmlAttrValue($key, $value)) {
                continue;
            }

            $attributes .= ' '.$this->escaper->escape($key, 'html_attr_relaxed').'="'.$this->escaper->escape($value).'"';
        }

        return $attributes;
    }

    public function __clone(): void
    {
        $this->rendered = [];
    }

    public function render(string $attribute): ?string
    {
        if (null === $value = HtmlExtension::htmlAttrValue($attribute, $this->attributes[$attribute] ?? null)) {
            return null;
        }

        $this->rendered[$attribute] = true;

        return $value;
    }

    /**
     * @return array<string, string|bool|int|float|null|iterable|\Stringable|\BackedEnum|AttributeValueInterface>
     */
    public function all(): array
    {
        return $this->attributes;
    }

    /**
     * Set default attributes. These are used if they are not already
     * defined.
     *
     * "class", "data-controller" and "data-action" are special, these defaults are
     * prepended to the existing attribute (if available).
     *
     * Values implementing Twig HTML extra's MergeableInterface (e.g. from the
     * "tailwind_classes" filter) are merged through the merge protocol, for any key.
     * Values of another type are merged as strings for these three keys, so an
     * iterable or an enum must implement MergeableInterface (e.g. through the
     * "html_attr_type" filter) to be merged.
     */
    public function defaults(iterable $attributes): self
    {
        if ($attributes instanceof StimulusAttributes) {
            $attributes = $attributes->toArray();
        }

        if ($attributes instanceof \Traversable) {
            $attributes = iterator_to_array($attributes);
        }

        foreach ($this->attributes as $key => $value) {
            if (!isset($attributes[$key])) {
                $attributes[$key] = $value;

                continue;
            }

            $default = $attributes[$key];

            if ($value instanceof MergeableInterface) {
                $attributes[$key] = $value->mergeInto($default);
            } elseif ($default instanceof MergeableInterface) {
                $attributes[$key] = $default->appendFrom($value);
            } elseif (\in_array($key, ['class', 'data-controller', 'data-action'], true)) {
                $attributes[$key] = "{$default} {$value}";
            } else {
                $attributes[$key] = $value;
            }
        }

        foreach (array_keys($this->rendered) as $attribute) {
            unset($attributes[$attribute]);
        }

        return new self($attributes, $this->escaper);
    }

    /**
     * Extract only these attributes.
     */
    public function only(string ...$keys): self
    {
        $attributes = [];

        foreach ($this->attributes as $key => $value) {
            if (\in_array($key, $keys, true)) {
                $attributes[$key] = $value;
            }
        }

        return new self($attributes, $this->escaper);
    }

    /**
     * Extract all but these attributes.
     */
    public function without(string ...$keys): self
    {
        $clone = clone $this;

        foreach ($keys as $key) {
            unset($clone->attributes[$key]);
        }

        return $clone;
    }

    public function remove($key): self
    {
        $attributes = $this->attributes;

        unset($attributes[$key]);

        return new self($attributes, $this->escaper);
    }

    public function nested(string $namespace): self
    {
        $attributes = [];

        foreach ($this->attributes as $key => $value) {
            if (
                str_contains($key, ':')
                && preg_match(self::NESTED_REGEX, $key, $matches) && $namespace === $matches[1]
            ) {
                $attributes[$matches[2]] = $value;
            }
        }

        return new self($attributes, $this->escaper);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->attributes);
    }

    public function has(string $attribute): bool
    {
        return \array_key_exists($attribute, $this->attributes);
    }

    public function count(): int
    {
        return \count($this->attributes);
    }
}
