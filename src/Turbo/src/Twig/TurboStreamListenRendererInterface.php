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

trigger_deprecation('symfony/ux-turbo', '3.1', 'The "%s" interface is deprecated since Symfony UX 3.1, use "%s" with turbo_stream_from() or the <twig:Turbo:Stream:From> Twig component instead. It will be removed in 4.0.', TurboStreamListenRendererInterface::class, \Symfony\UX\Turbo\StreamSourceRendererInterface::class);

/**
 * Render turbo stream attributes.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 *
 * @deprecated since Symfony UX 3.1, use {@see \Symfony\UX\Turbo\StreamSourceRendererInterface} with turbo_stream_from() or the <twig:Turbo:Stream:From> Twig component instead. Will be removed in 4.0.
 */
interface TurboStreamListenRendererInterface
{
    /**
     * @param string|object        $topic
     * @param array<string, mixed> $eventSourceOptions
     */
    public function renderTurboStreamListen(Environment $env, $topic, array $eventSourceOptions = []): string;
}
