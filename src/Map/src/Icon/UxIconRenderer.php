<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Icon;

use Symfony\UX\Icons\IconRendererInterface;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 * @internal
 */
class UxIconRenderer
{
    /**
     * @var array<string, string>
     */
    private array $rendered = [];

    public function __construct(
        private readonly ?IconRendererInterface $renderer,
    ) {
    }

    /**
     * @param array<string, string|bool> $attributes
     */
    public function render(string $name, array $attributes = []): string
    {
        if (null === $this->renderer) {
            throw new \LogicException('You cannot use an UX Icon as the "UX Icons" package is not installed. Try running "composer require symfony/ux-icons" to install it.');
        }

        // Markers of a map overwhelmingly share the same icon, so the same SVG
        // was rebuilt once per marker.
        return $this->rendered[$name.'|'.serialize($attributes)] ??= $this->renderer->renderIcon($name, [
            'xmlns' => 'http://www.w3.org/2000/svg',
            ...$attributes,
        ]);
    }
}
