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

/**
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
final class ProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('ux.image.provider_registry')) {
            return;
        }

        $registryDefinition = $container->getDefinition('ux.image.provider_registry');
        $taggedServices = $container->findTaggedServiceIds('ux.image.provider');

        foreach ($taggedServices as $id => $tags) {
            $registryDefinition->addMethodCall('addProvider', [new Reference($id)]);
        }
    }
}
