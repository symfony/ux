<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo;

/**
 * Renders a Turbo stream source HTML element.
 *
 * @author Sébastien Jean <sebastien.jean76@gmail.com>
 */
interface StreamSourceRendererInterface
{
    /**
     * @param string|object|array<string|object> $topics
     * @param array<string, mixed>               $options
     */
    public function render(string|object|array $topics, array $options = []): string;
}
