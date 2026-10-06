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

use Symfony\Contracts\Service\ResetInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class IconRenderer implements IconRendererInterface, ResetInterface
{
    private const MAX_RENDERED = 1000;

    /** @var array<string, string> */
    private array $rendered = [];

    /**
     * @param array<string, mixed>                               $defaultIconAttributes
     * @param array<string, string>                              $iconAliases
     * @param array<string, array<string, mixed>>                $iconSetsAttributes
     * @param array<string, array<string, array<string, mixed>>> $iconSuffixAttributes
     */
    public function __construct(
        private readonly IconRegistryInterface $registry,
        private readonly array $defaultIconAttributes = [],
        private readonly array $iconAliases = [],
        private readonly array $iconSetsAttributes = [],
        private readonly array $iconSuffixAttributes = [],
    ) {
    }

    /**
     * Renders an icon.
     *
     * Provided attributes are merged with the default attributes.
     * Existing icon attributes are then merged with those new attributes.
     *
     * Precedence order:
     *   Icon file < Renderer configuration < Renderer invocation
     */
    public function renderIcon(string $name, array $attributes = []): string
    {
        if (null === $key = self::cacheKey($name, $attributes)) {
            return $this->doRenderIcon($name, $attributes);
        }

        if (isset($this->rendered[$key])) {
            return $this->rendered[$key];
        }

        if (\count($this->rendered) >= self::MAX_RENDERED) {
            $this->rendered = [];
        }

        return $this->rendered[$key] = $this->doRenderIcon($name, $attributes);
    }

    public function reset(): void
    {
        $this->rendered = [];
    }

    /**
     * Pages render the same few icons over and over, and the HTML only depends on the name and the attributes.
     *
     * Returns null when an attribute value is not a scalar: Icon::withAttributes() rejects it anyway.
     */
    private static function cacheKey(string $name, array $attributes): ?string
    {
        foreach ($attributes as $value) {
            if (!\is_scalar($value)) {
                return null;
            }
        }

        return serialize([$name, $attributes]);
    }

    private function doRenderIcon(string $name, array $attributes): string
    {
        $iconName = $this->iconAliases[$name] ?? $name;

        $icon = $this->registry->get($iconName);

        $setAttributes = $suffixAttributes = [];
        if (0 < (int) $pos = strpos($name, ':')) {
            [$setAttributes, $suffixAttributes] = $this->resolveAttributes($name, $pos);
        } elseif ($iconName !== $name && $pos = strpos($iconName, ':')) {
            [$setAttributes, $suffixAttributes] = $this->resolveAttributes($iconName, $pos);
        }

        $icon = $icon->withAttributes([
            ...$this->defaultIconAttributes,
            ...$setAttributes,
            ...$suffixAttributes,
            ...$attributes,
        ]);

        return self::setAriaHidden($icon)->toHtml();
    }

    /**
     * Resolves set and suffix attributes for a given icon name.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function resolveAttributes(string $name, int $pos): array
    {
        $prefix = substr($name, 0, $pos);

        return [
            $this->iconSetsAttributes[$prefix] ?? [],
            $this->findSuffixAttributes($prefix, substr($name, $pos + 1)),
        ];
    }

    /**
     * Finds attributes for the matching suffix.
     * Suffixes are pre-sorted by length (longest first).
     *
     * @return array<string, mixed>
     */
    private function findSuffixAttributes(string $prefix, string $iconNamePart): array
    {
        foreach ($this->iconSuffixAttributes[$prefix] ?? [] as $suffix => $config) {
            if ('' === $suffix || str_ends_with($iconNamePart, '-'.$suffix)) {
                return $config;
            }
        }

        return [];
    }

    /**
     * Set `aria-hidden=true` if not defined & no textual alternative provided.
     */
    private static function setAriaHidden(Icon $icon): Icon
    {
        // "false" marks an omitted attribute, it provides no textual alternative
        $attributes = array_filter($icon->getAttributes(), static fn ($value) => false !== $value);

        if (!isset($attributes['aria-hidden']) && !isset($attributes['aria-label']) && !isset($attributes['aria-labelledby']) && !isset($attributes['title'])) {
            return $icon->withAttributes(['aria-hidden' => 'true']);
        }

        return $icon;
    }
}
