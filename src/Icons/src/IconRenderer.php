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
     * @param iterable<IconPreRendererInterface> $preRenderers
     */
    public function __construct(
        private readonly IconRegistryInterface $registry,
        private readonly array $defaultIconAttributes = [],
        private readonly ?array $iconAliases = [],
        private readonly iterable $preRenderers = [],
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
        $name = $this->iconAliases[$name] ?? $name;

        $icon = $this->registry->get($name)
            ->withAttributes($this->defaultIconAttributes)
            ->withAttributes($attributes);

        foreach ($this->preRenderers as $preRenderer) {
            $icon = $preRenderer($name, $icon);
        }

        return $icon->toHtml();
    }
}
