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
use Symfony\UX\Turbo\TurboFrame;
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
        private readonly TurboFrame $turboFrame,
        private ContainerInterface $streamSourceRenderers,
    ) {
    }

    /**
     * @param string|object|array<string|object> $topics
     */
    public function renderTurboStreamFrom(Environment $env, string|object|array $topics, bool $private = false, ?string $transport = null): string
    {
        $transport ??= $this->defaultTransport;

        if (!$this->streamSourceRenderers->has($transport)) {
            throw new \InvalidArgumentException(\sprintf('The Turbo stream transport "%s" does not exist.', $transport));
        }

        return $this->streamSourceRenderers->get($transport)->render($topics, ['private' => $private]);
    }

    /**
     * @param object|string|array<object|string> $topic
     * @param array<string, mixed>               $options
     *
     * @deprecated since Symfony UX 3.1, use renderTurboStreamFrom() or the <twig:Turbo:Stream:From> Twig component instead. Will be removed in 4.0.
     */
    public function renderTurboStreamListen(Environment $env, $topic, ?string $transport = null, array $options = []): string
    {
        trigger_deprecation('symfony/ux-turbo', '3.1', 'The "%s()" method is deprecated since Symfony UX 3.1, use "%s()" or the <twig:Turbo:Stream:From> Twig component instead. It will be removed in 4.0.', __METHOD__, __CLASS__.'::renderTurboStreamFrom');
        if (\array_key_exists('hub', $options) && $transport !== $options['hub']) {
            throw new \InvalidArgumentException('When passing the "transport" option, the "hub" key in options is not allowed.');
        }

        $options['hub'] = $transport ??= $this->defaultTransport;

        if (!$this->turboStreamListenRenderers->has($transport)) {
            throw new \InvalidArgumentException(\sprintf('The Turbo stream transport "%s" does not exist.', $transport));
        }

        if (\is_array($topic)) {
            $topic = new TopicSet($topic);
        }

        $renderer = $this->turboStreamListenRenderers->get($transport);

        return $renderer->renderTurboStreamListen($env, $topic, $options);
    }

    public function isTurboFrameRequest(): bool
    {
        return $this->turboFrame->isFrameRequest();
    }

    public function getTurboFrameRequestId(): ?string
    {
        return $this->turboFrame->getRequestId();
    }
}
