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
     * @param array<string, mixed>                $defaultIconAttributes
     * @param array<string, string>               $iconAliases
     * @param array<string, array<string, mixed>> $iconSetsAttributes
     */
    public function __construct(
        private readonly IconRegistryInterface $registry,
        private readonly array $defaultIconAttributes = [],
        private readonly array $iconAliases = [],
        private readonly array $iconSetsAttributes = [],
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

        if (0 < (int) $pos = strpos($name, ':')) {
            $setAttributes = $this->iconSetsAttributes[substr($name, 0, $pos)] ?? [];
        } elseif ($iconName !== $name && 0 < (int) $pos = strpos($iconName, ':')) {
            $setAttributes = $this->iconSetsAttributes[substr($iconName, 0, $pos)] ?? [];
        }
        $icon = $icon->withAttributes([...$this->defaultIconAttributes, ...($setAttributes ?? []), ...$attributes]);

        foreach ($this->getPreRenderers() as $preRenderer) {
            $icon = $preRenderer($icon);
        }

        return $icon->toHtml();
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
