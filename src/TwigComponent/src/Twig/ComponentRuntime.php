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
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\TwigComponent\ComponentRendererInterface;
use Symfony\UX\TwigComponent\ComponentStack;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Twig\Environment;
use Twig\TemplateWrapper;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class ComponentRuntime implements ResetInterface
{
    /** @var array<string, TemplateWrapper> */
    private array $embeddedParentTemplates = [];

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
            return $this->renderers->get($normalized)->render($props);
        }

        return $this->renderer->createAndRender($name, $props);
    }

    /**
     * @param array<string, mixed> $props
     * @param array<string, mixed> $context
     */
    public function startEmbedComponent(string $name, array $props, array $context, string $hostTemplateName, int $index): PreRenderEvent
    {
        return $this->renderer->startEmbeddedComponentRender($name, $props, $context, $hostTemplateName, $index);
    }

    /**
     * Handing the embedded template a loaded parent spares Twig from resolving its name on every render.
     */
    public function getEmbeddedParentTemplate(Environment $env, string $template): TemplateWrapper
    {
        return $this->embeddedParentTemplates[$template] ??= $env->load($template);
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

    public function reset(): void
    {
        $this->embeddedParentTemplates = [];
    }
}
