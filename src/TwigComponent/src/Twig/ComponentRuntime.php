<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Twig;

use Symfony\UX\TwigComponent\ComponentRenderer;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class ComponentRuntime
{
    public function __construct(
        private readonly ComponentRenderer $renderer,
    ) {
    }

    /**
     * @param array<string, mixed> $props
     */
    public function render(string $name, array $props = []): string
    {
        return $this->renderer->createAndRender($name, $props);
    }

    /**
     * @param array<string, mixed> $props
     */
    public function preRender(string $name, array $props): ?string
    {
        return $this->renderer->preCreateForRender($name, $props);
    }

    /**
     * @param array<string, mixed> $props
     * @param array<string, mixed> $context
     */
    public function startEmbedComponent(string $name, array $props, array $context, string $hostTemplateName, int $index): PreRenderEvent
    {
        return $this->renderer->startEmbeddedComponentRender($name, $props, $context, $hostTemplateName, $index);
    }

    public function finishEmbedComponent(): void
    {
        $this->renderer->finishEmbeddedComponentRender();
    }
}
