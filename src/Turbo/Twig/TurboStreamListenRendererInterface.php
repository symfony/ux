<?php

declare(strict_types=1);

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
    public function renderTurboStreamListen(Environment $env, $topic): string;
}
