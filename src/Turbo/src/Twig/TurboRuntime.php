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

use Psr\Container\ContainerInterface;
use Symfony\UX\Turbo\Bridge\Mercure\TopicSet;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 * @author Pierre Ambroise <pierre27.ambroise@gmail.com>
 *
 * @internal
 */
final class TurboRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private ContainerInterface $turboStreamListenRenderers,
        private readonly string $defaultTransport,
    ) {
    }

    /**
     * @param object|string|array<object|string> $topic
     * @param array<string, mixed>               $options
     */
    public function renderTurboStreamListen(Environment $env, $topic, ?string $transport = null, array $options = []): string
    {
        $options['transport'] = $transport ??= $this->defaultTransport;

        if (!$this->turboStreamListenRenderers->has($transport)) {
            throw new \InvalidArgumentException(\sprintf('The Turbo stream transport "%s" does not exist.', $transport));
        }

        if (\is_array($topic)) {
            $topic = new TopicSet($topic);
        }

        $renderer = $this->turboStreamListenRenderers->get($transport);

        return $renderer instanceof TurboStreamListenRendererWithOptionsInterface
            ? $renderer->renderTurboStreamListen($env, $topic, $options) // @phpstan-ignore-line
            : $renderer->renderTurboStreamListen($env, $topic);
    }
}
