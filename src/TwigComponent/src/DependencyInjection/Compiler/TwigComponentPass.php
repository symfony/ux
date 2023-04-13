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
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class TwigComponentPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $componentConfig = [];

        $componentReferences = [];
        $componentNames = [];
        foreach ($container->findTaggedServiceIds('twig.component') as $id => $tags) {
            $definition = $container->findDefinition($id);

            // component services must not be shared
            $definition->setShared(false);

            foreach ($tags as $tag) {
                if (!\array_key_exists('key', $tag)) {
                    $fqcn = $definition->getClass();
                    $name = substr($fqcn, strrpos($fqcn, '\\') + 1);
                    if (\in_array($name, $componentNames, true)) {
                        throw new LogicException(sprintf('Failed creating the "%s" component with the automatic name "%s": another component already has this name. To fix this, give the component an explicit name (hint: using "%s" will override the existing component).', $fqcn, $name, $name));
                    }
                    $tag['key'] = $name;
                }

                $tag['service_id'] = $id;
                $tag['class'] = $definition->getClass();
                $tag['template'] = $tag['template'] ?? sprintf('components/%s.html.twig', str_replace(':', '/', $tag['key']));
                $componentConfig[$tag['key']] = $tag;
                $componentReferences[$tag['key']] = new Reference($id);
                $componentNames[] = $tag['key'];
            }
        }

        $factoryDefinition = $container->findDefinition('ux.twig_component.component_factory');
        $factoryDefinition->setArgument(0, ServiceLocatorTagPass::register($container, $componentReferences));
        $factoryDefinition->setArgument(3, $componentConfig);
    }
}
