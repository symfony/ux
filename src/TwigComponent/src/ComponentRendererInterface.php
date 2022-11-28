<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

interface ComponentRendererInterface
{
    /**
     * Create and render a twig component.
     */
    public function createAndRender(string $name, array $props = []): string;
}
