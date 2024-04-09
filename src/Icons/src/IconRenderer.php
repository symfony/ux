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
final class IconRenderer
{
    public function __construct(
        private readonly IconRegistryInterface $registry,
        private readonly array $defaultIconAttributes = [],
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
     *
     * @param array<string,string|bool> $attributes
     */
    public function renderIcon(string $name, array $attributes = []): string
    {
        return $this->registry->get($name)
            ->withAttributes($this->getIconAttributes($name, $attributes))
            ->toHtml()
        ;
    }

    private function getIconAttributes(string $name, array $attributes): array
    {
        $iconAttributes = $this->defaultIconAttributes;

        // Add aria-hidden attribute
        if ([] === array_intersect(['aria-hidden',  'aria-label', 'aria-labelledby', 'title'], array_keys($attributes))) {
            $iconAttributes['aria-hidden'] = 'true';
        }

        return [...$iconAttributes, ...$attributes];
    }
}
