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
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentRenderer;
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
            ComponentFactory::class,
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('component', [$this, 'render'], ['is_safe' => ['all']]),
        ];
    }

    public function getTokenParsers(): array
    {
        return [
            new ComponentTokenParser(fn () => $this->container->get(ComponentFactory::class)),
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

    public function embeddedContext(string $name, array $props, array $context): array
    {
        try {
            return $this->container->get(ComponentRenderer::class)->embeddedContext($name, $props, $context);
        } catch (\Throwable $e) {
            $this->throwRuntimeError($name, $e);
        }
    }

    private function throwRuntimeError(string $name, \Throwable $e): void
    {
        if (!($e instanceof \Exception)) {
            $e = new \Exception($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
        throw new RuntimeError(sprintf('Error rendering "%s" component: %s', $name, $e->getMessage()), previous: $e);
    }
}
