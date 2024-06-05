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
     */
    public function renderIcon(string $name, array $attributes = []): string
    {
        $icon = $this->registry->get($name)
            ->withAttributes($this->defaultIconAttributes)
            ->withAttributes($attributes);

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
