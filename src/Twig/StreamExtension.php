<?php

/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\UX\Turbo\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig helpers to generated the Turbo Stream elements.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class StreamExtension extends AbstractExtension
{
    private const ACTIONS = [
        'append' => true,
        'prepend' => true,
        'replace' => true,
        'update' => true,
        'remove' => true,
    ];

    public function __construct(
        private ?string $mercureHub = null,
    ) {}

    public function getFunctions(): iterable
    {
        yield new TwigFunction('turbo_stream_start', [$this, 'turboStreamStart'], ['is_safe' => ['html']]);
        yield new TwigFunction('turbo_stream_end', [$this, 'turboStreamEnd'], ['is_safe' => ['html']]);
        yield new TwigFunction('turbo_stream_from_start', [$this, 'turboStreamFromStart'], ['is_safe' => ['html']]);
        yield new TwigFunction('turbo_stream_from_end', [$this, 'turboStreamFromEnd'], ['is_safe' => ['html']]);
    }

    /**
     * @param array<string, string> $attrs
     *
     * @throws \InvalidArgumentException
     */
    public function turboStreamStart(string $action, string $target, array $attrs = []): string
    {
        if (!isset(self::ACTIONS[$action])) {
            throw new \InvalidArgumentException(sprintf('The action "%s" doesn\'t exist. Supported actions are: "%s".', $action, implode('", "', array_keys(self::ACTIONS))));
        }

        $a = [];
        foreach ($attrs + ['action' => $action, 'target' => $target] as $k => $v) {
            $a[] = sprintf('%s="%s"', htmlspecialchars($k, ENT_QUOTES), htmlspecialchars($v, ENT_QUOTES));
        }

        return sprintf('<turbo-stream %s><template>', implode(' ', $a));
    }

    public function turboStreamEnd(): string
    {
        return '</template></turbo-stream>';
    }

    /**
     * @param array<string, string> $attrs
     */
    public function turboStreamFromStart(string $id, array $attrs = []): string
    {
        if (null === $this->mercureHub) {
            throw new \RuntimeException('The "turbo.mercure.subscribe_url" configuration key must be set to use "turbo_stream_from_start()".');
        }

        $a = [];
        foreach ($attrs + ['data-turbo-stream-topic-value' => $id, 'data-turbo-stream-hub-value' => $this->mercureHub, 'data-controller' => 'turbo-stream'] as $k => $v) {
            $a[] = sprintf('%s="%s"', htmlspecialchars($k, ENT_QUOTES), htmlspecialchars($v, ENT_QUOTES));
        }

        return sprintf('<div %s>', implode(' ', $a));
    }

    public function turboStreamFromEnd(): string
    {
        return '</div>';
    }
}
