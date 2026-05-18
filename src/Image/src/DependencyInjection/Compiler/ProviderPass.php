<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\Image\Provider\ProviderRegistry;

/**
 * Registers all tagged providers into the ProviderRegistry.
 *
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
class ProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ProviderRegistry::class)) {
            return;
        }

        $registry = $container->getDefinition(ProviderRegistry::class);
        $taggedServices = $container->findTaggedServiceIds('ux_image.provider');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $name = $attributes['name'] ?? $id;
                $registry->addMethodCall('addProvider', [new Reference($id)]);
            }
        }
    }
}
