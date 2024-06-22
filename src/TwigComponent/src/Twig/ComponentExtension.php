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
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\UX\TwigComponent\ComponentRenderer;
use Symfony\UX\TwigComponent\CVA;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ComponentExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public static function getSubscribedServices(): array
    {
        return [
            ComponentRenderer::class,
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('component', [$this, 'render'], ['is_safe' => ['all']]),
            new TwigFunction('cva', [$this, 'cva']),
        ];
    }

    public function getTokenParsers(): array
    {
        return [
            new ComponentTokenParser(),
            new PropsTokenParser(),
        ];
    }

    public function render(string $name, array $props = []): string
    {
        try {
            return $this->container->get(ComponentRenderer::class)->createAndRender($name, $props);
        } catch (\Throwable $e) {
            $this->throwRuntimeError($name, $e);
        }
    }

    public function extensionPreCreateForRender(string $name, array $props): ?string
    {
        try {
            return $this->container->get(ComponentRenderer::class)->preCreateForRender($name, $props);
        } catch (\Throwable $e) {
            $this->throwRuntimeError($name, $e);
        }
    }

    public function startEmbeddedComponentRender(string $name, array $props, array $context, string $hostTemplateName, int $index): PreRenderEvent
    {
        try {
            return $this->container->get(ComponentRenderer::class)->startEmbeddedComponentRender($name, $props, $context, $hostTemplateName, $index);
        } catch (\Throwable $e) {
            $this->throwRuntimeError($name, $e);
        }
    }

    public function finishEmbeddedComponentRender(): void
    {
        $this->container->get(ComponentRenderer::class)->finishEmbeddedComponentRender();
    }

    /**
     * Create a CVA instance.
     *
     * base some base class you want to have in every matching recipes
     * variants your recipes class
     * compoundVariants compounds allow you to add extra class when multiple variation are matching in the same time
     * defaultVariants allow you to add a default class when no recipe is matching
     *
     * @see https://symfony.com/bundles/ux-twig-component/current/index.html#component-with-complex-variants-cva
     *
     * @param array{
     *   base: string|string[]|null,
     *   variants: array<string, array<string, string|string[]>>,
     *   compoundVariants: list<array<string, string|string[]>>,
     *   defaultVariants: array<string, string>,
     * } $cva
     */
    public function cva(array $cva): CVA
    {
        return new CVA(
            $cva['base'] ?? '',
            $cva['variants'] ?? [],
            $cva['compoundVariants'] ?? [],
            $cva['defaultVariants'] ?? [],
        );
    }

    private function throwRuntimeError(string $name, \Throwable $e): void
    {
        // if it's already a Twig RuntimeError, just rethrow it
        if ($e instanceof RuntimeError) {
            throw $e;
        }

        if (!($e instanceof \Exception)) {
            $e = new \Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        throw new RuntimeError(\sprintf('Error rendering "%s" component: %s', $name, $e->getMessage()), previous: $e);
    }
}
