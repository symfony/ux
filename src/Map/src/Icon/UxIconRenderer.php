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

        return $this->renderer->renderIcon($name, [
            'xmlns' => 'http://www.w3.org/2000/svg',
            ...$attributes,
        ]);
    }
}
