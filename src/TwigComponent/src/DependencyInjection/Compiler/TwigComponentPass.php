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
        $componentClassMap = [];
        $componentNames = [];
        $componentDefaults = $container->getParameter('ux.twig_component.component_defaults');
        $container->getParameterBag()->remove('ux.twig_component.component_defaults');
        $legacyAutoNaming = $container->hasParameter('ux.twig_component.legacy_autonaming');
        $container->getParameterBag()->remove('ux.twig_component.legacy_autonaming');

        foreach ($container->findTaggedServiceIds('twig.component') as $id => $tags) {
            $definition = $container->findDefinition($id);

            // component services must not be shared
            $definition->setShared(false);
            $fqcn = $definition->getClass();
            $defaults = $this->findMatchingDefaults($fqcn, $componentDefaults);

            foreach ($tags as $tag) {
                if (!\array_key_exists('key', $tag)) {
                    if ($legacyAutoNaming) {
                        $name = substr($fqcn, strrpos($fqcn, '\\') + 1);
                    } else {
                        if (null === $defaults) {
                            throw new LogicException(\sprintf('Could not generate a component name for class "%s": no matching namespace found under the "twig_component.defaults" to use as a root. Check the config or give your component an explicit name.', $fqcn));
                        }

                        $name = str_replace('\\', ':', substr($fqcn, \strlen($defaults['namespace'])));
                        if ($defaults['name_prefix']) {
                            $name = \sprintf('%s:%s', $defaults['name_prefix'], $name);
                        }
                    }
                    if (\in_array($name, $componentNames, true)) {
                        throw new LogicException(\sprintf('Failed creating the "%s" component with the automatic name "%s": another component already has this name. To fix this, give the component an explicit name (hint: using "%s" will override the existing component).', $fqcn, $name, $name));
                    }

                    $tag['key'] = $name;
                }

                $tag['service_id'] = $id;
                $tag['class'] = $definition->getClass();
                $tag['template'] = $tag['template'] ?? $this->calculateTemplate($tag['key'], $defaults);
                $componentConfig[$tag['key']] = $tag;
                $componentReferences[$tag['key']] = new Reference($id);
                $componentNames[] = $tag['key'];
                $componentClassMap[$tag['class']] = $tag['key'];
            }
        }

        $factoryDefinition = $container->findDefinition('ux.twig_component.component_factory');
        $factoryDefinition->setArgument(1, ServiceLocatorTagPass::register($container, $componentReferences));
        $factoryDefinition->setArgument(4, $componentConfig);
        $factoryDefinition->setArgument(5, $componentClassMap);

        $debugCommandDefinition = $container->findDefinition('ux.twig_component.command.debug');
        $debugCommandDefinition->setArgument(3, $componentClassMap);
    }

    private function findMatchingDefaults(string $className, array $componentDefaults): ?array
    {
        foreach ($componentDefaults as $namespace => $defaults) {
            if (str_starts_with($className, $namespace)) {
                return array_merge(['namespace' => $namespace], $defaults);
            }
        }

        return null;
    }

    private function calculateTemplate(string $componentName, ?array $defaults): string
    {
        $directory = $defaults && isset($defaults['template_directory']) ? $defaults['template_directory'] : 'components';

        // if a name_prefix was added to the name, don't include it in the template path
        if ($defaults && $defaults['name_prefix'] ?? null) {
            $componentName = substr($componentName, \strlen($defaults['name_prefix']) + 1);
        }

        return \sprintf('%s/%s.html.twig', rtrim($directory, '/'), str_replace(':', '/', $componentName));
    }
}
