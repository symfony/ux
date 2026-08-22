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

use Psr\Container\ContainerInterface;
use Symfony\UX\TwigComponent\ComponentRendererInterface;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Twig\Extra\Html\HtmlAttr\AttributeValueInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class ComponentRuntime
{
    public function __construct(
        private readonly ComponentRendererInterface $renderer,
        private readonly ContainerInterface $renderers,
        private readonly ComponentStack $componentStack,
    ) {
    }

    public function finishEmbedComponent(): void
    {
        $this->renderer->finishEmbeddedComponentRender();
    }

    /**
     * @param array<string, mixed> $props
     */
    public function preRender(string $name, array $props): ?string
    {
        return $this->renderer->preCreateForRender($name, $props);
    }

    public function render(string $name, array $props = []): string
    {
        if ($this->renderers->has($normalized = strtolower($name))) {
            return $this->renderers->get($normalized)->render(self::normalizeRendererProps($props));
        }

        return $this->renderer->createAndRender($name, $props);
    }

    /**
     * @param array<string, mixed> $props
     */
    /**
     * Custom renderers (e.g. "ux:icon" or "ux:map") receive the props as plain values: the typed attribute
     * values of twig/html-extra are resolved the way ComponentAttributes renders them, a null value
     * suppressing the attribute (including the defaults of the renderer), and Stringable values are cast.
     *
     * @param array<string, mixed> $props
     *
     * @return array<string, mixed>
     */
    private static function normalizeRendererProps(array $props): array
    {
        foreach ($props as $key => $value) {
            if ($value instanceof AttributeValueInterface) {
                $props[$key] = $value->getValue() ?? false;
            } elseif ($value instanceof \Stringable) {
                $props[$key] = (string) $value;
            }
        }

        return $props;
    }

    public function startEmbedComponent(string $name, array $props, array $context, string $hostTemplateName, int $index): PreRenderEvent
    {
        return $this->renderer->startEmbeddedComponentRender($name, $props, $context, $hostTemplateName, $index);
    }

    public function provide(string $key, mixed $value): void
    {
        $current = $this->componentStack->getCurrentComponent();
        if (null === $current) {
            throw new \LogicException(\sprintf('The "provide()" Twig function cannot be called outside of a component template, "%s" key was being provided.', $key));
        }

        $current->provide($key, $value);
    }

    public function inject(string $key, mixed $default = null): mixed
    {
        $skippedSelf = false;
        foreach ($this->componentStack as $mounted) {
            if (!$skippedSelf) {
                $skippedSelf = true;
                continue;
            }

            if ($mounted->hasProvided($key)) {
                return $mounted->getProvided($key);
            }
        }

        return $default;
    }
}
