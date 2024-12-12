<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Twig;

use Twig\Environment;

/**
 * Render turbo stream attributes.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
interface TurboStreamListenRendererInterface
{
    /**
     * @param string|object $topic
     */
    public function renderTurboStreamListen(Environment $env, $topic /* , array $eventSourceOptions = [] */): string;
}
