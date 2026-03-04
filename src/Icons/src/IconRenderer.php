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
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class IconRenderer implements IconRendererInterface
{
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

        foreach ($this->getPreRenderers() as $preRenderer) {
            $icon = $preRenderer($icon);
        }

        return $icon->toHtml();
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
     * @return iterable<callable(Icon): Icon>
     */
    private function getPreRenderers(): iterable
    {
        yield self::setAriaHidden(...);
    }

    /**
     * Set `aria-hidden=true` if not defined & no textual alternative provided.
     */
    private static function setAriaHidden(Icon $icon): Icon
    {
        if ([] === array_intersect(['aria-hidden', 'aria-label', 'aria-labelledby', 'title'], array_keys($icon->getAttributes()))) {
            return $icon->withAttributes(['aria-hidden' => 'true']);
        }

        return $icon;
    }
}
