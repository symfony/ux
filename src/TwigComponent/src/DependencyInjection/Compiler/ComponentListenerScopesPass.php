<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\TwigComponent\Event\PostMountEvent;
use Symfony\UX\TwigComponent\Event\PostRenderEvent;
use Symfony\UX\TwigComponent\Event\PreCreateForRenderEvent;
use Symfony\UX\TwigComponent\Event\PreMountEvent;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;

/**
 * Finds the component events whose listeners all only care about some components.
 *
 * A listener tagged "ux.twig_component.scoped_listener" declares the components it acts on: with a "metadata"
 * attribute, the components whose metadata holds a truthy value under that key; with a "props" attribute, the
 * components rendered with at least one of these props. When every listener of an event declares a scope, and
 * those with a "metadata" attribute all name the same key, the event is only dispatched for the components in
 * the scope of at least one of them.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ComponentListenerScopesPass implements CompilerPassInterface
{
    public const SCOPED_LISTENER_TAG = 'ux.twig_component.scoped_listener';

    private const EVENTS = [
        PreCreateForRenderEvent::class,
        PreMountEvent::class,
        PostMountEvent::class,
        PreRenderEvent::class,
        PostRenderEvent::class,
    ];

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('ux.twig_component.component_factory') || !$container->has('event_dispatcher')) {
            return;
        }

        $scopes = [];
        foreach ($container->findTaggedServiceIds(self::SCOPED_LISTENER_TAG) as $id => $tags) {
            $scope = array_filter([
                'metadata' => $tags[0]['metadata'] ?? null,
                'props' => (array) ($tags[0]['props'] ?? []),
            ]);
            $scopes[$id] = $scope ?: null;
        }

        if (!$scopes) {
            return;
        }

        $listenerScopes = [];
        foreach ($container->findDefinition('event_dispatcher')->getMethodCalls() as [$method, $arguments]) {
            if ('addListener' !== $method || !\in_array($arguments[0] ?? null, self::EVENTS, true)) {
                continue;
            }

            $listenerScopes[$arguments[0]][] = $this->scopeOf($arguments[1] ?? null, $scopes);
        }

        $eventScopes = [];
        foreach ($listenerScopes as $eventClass => $eventListenerScopes) {
            if (\in_array(null, $eventListenerScopes, true)) {
                continue;
            }

            $metadataKeys = array_unique(array_column($eventListenerScopes, 'metadata'));
            if (\count($metadataKeys) > 1) {
                continue;
            }

            $props = array_fill_keys(array_merge(...array_column($eventListenerScopes, 'props')), true);
            $eventScopes[$eventClass] = [$metadataKeys[0] ?? null, $props, \count($eventListenerScopes)];
        }

        $container->getDefinition('ux.twig_component.component_factory')->setArgument(7, $eventScopes);
    }

    /**
     * @param array<string, array{metadata?: string, props?: list<string>}|null> $scopes
     *
     * @return array{metadata?: string, props?: list<string>}|null
     */
    private function scopeOf(mixed $listener, array $scopes): ?array
    {
        if (!\is_array($listener) || !($listener[0] ?? null) instanceof ServiceClosureArgument) {
            return null;
        }

        $reference = $listener[0]->getValues()[0] ?? null;

        return $reference instanceof Reference ? $scopes[(string) $reference] ?? null : null;
    }
}
