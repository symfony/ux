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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 *
 * @internal
 */
final class TwigComponentPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $componentConfig = [];

        foreach ($container->findTaggedServiceIds('twig.component') as $id => $tags) {
            $definition = $container->findDefinition($id);

            // component services must not be shared
            $definition->setShared(false);

            foreach ($tags as $tag) {
                if (!\array_key_exists('key', $tag)) {
                    throw new LogicException(sprintf('"twig.component" tag for service "%s" requires a "key" attribute.', $id));
                }

                $tag['service_id'] = $id;
                $tag['class'] = $definition->getClass();
                $tag['template'] = $tag['template'] ?? "components/{$tag['key']}.html.twig";
                $componentConfig[$tag['key']] = $tag;
            }
        }

        $container->findDefinition('ux.twig_component.component_factory')
            ->setArgument(2, $componentConfig)
        ;
    }
}
