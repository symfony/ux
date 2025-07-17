<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Renderer;

use Symfony\UX\Map\Map;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
interface RendererInterface extends \Stringable
{
    /**
     * @param array<string, string|bool> $attributes an array of HTML attributes
     */
    public function renderMap(Map $map, array $attributes = []): string;
}
