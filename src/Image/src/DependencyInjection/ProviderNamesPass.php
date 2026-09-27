<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Hands the names of the installed providers to the URL generator, so it can reject an "operations" key that names none of them.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ProviderNamesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('ux_image.url_generator')) {
            return;
        }

        $names = [];
        foreach ($container->findTaggedServiceIds('ux_image.provider_factory') as $tags) {
            foreach ($tags as $attributes) {
                // A factory tagged without a name would make the list incomplete and reject its valid keys.
                if (!isset($attributes['name'])) {
                    return;
                }

                $names[] = $attributes['name'];
            }
        }
        sort($names);

        $container->getDefinition('ux_image.url_generator')->setArgument(1, $names);
    }
}
