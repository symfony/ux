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

use Symfony\UX\Icons\Exception\IconNotFoundException;

/**
 * @author Simon André <smn.andre@gmail.com>
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface IconRendererInterface
{
    /**
     * Renders an icon by its name and returns the SVG string.
     *
     * @param string                     $name       the icon name, optionally prefixed with the icon set
     * @param array<string, string|bool> $attributes an array of HTML attributes
     *
     * @throws IconNotFoundException
     *
     * @example
     *  $iconRenderer->renderIcon('arrow-right');
     *  // Renders the "arrow-right" icon from the default icons directory.
     *
     *  $iconRenderer->renderIcon('lucide:heart', ['class' => 'color-red']);
     *  // Renders the "heart" icon from the "lucide" icon set, with the "color-red" class.
     */
    public function renderIcon(string $name, array $attributes = []): string;
}
