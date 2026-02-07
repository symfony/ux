<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\Native\Attribute\AsNativeConfiguration;
use Symfony\UX\Native\Configuration\Configuration;

final class ConfigurationCompilerPass implements CompilerPassInterface
{
    private const TAG = 'ux_native.configuration';
    private const PROVIDER_TAG = 'ux_native.configuration_provider';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('.ux_native.configuration_builder')) {
            return;
        }

        $this->registerAttributeConfigurations($container);

        $definition = $container->getDefinition('.ux_native.configuration_builder');

        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!\array_key_exists('path', $attributes)) {
                    throw new \InvalidArgumentException(\sprintf('The path is missing for "%s"', $id));
                }
                $definition->addMethodCall('add', [$attributes['path'], new Reference($id)]);
            }
        }
    }

    /**
     * Scans classes tagged with #[AsNativeConfigurationProvider] for methods
     * annotated with #[AsNativeConfiguration] and registers them as tagged
     * Configuration services.
     */
    private function registerAttributeConfigurations(ContainerBuilder $container): void
    {
        $providerIds = $container->findTaggedServiceIds(self::PROVIDER_TAG);

        foreach ($providerIds as $serviceId => $tags) {
            $definition = $container->getDefinition($serviceId);
            $class = $definition->getClass() ?? $serviceId;

            try {
                $reflectionClass = new \ReflectionClass($class);
            } catch (\ReflectionException) {
                continue;
            }

            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                $attributes = $method->getAttributes(AsNativeConfiguration::class);

                if ([] === $attributes) {
                    continue;
                }

                if ($method->isStatic()) {
                    throw new \InvalidArgumentException(\sprintf('The method "%s::%s()" annotated with #[AsNativeConfiguration] must not be static.', $class, $method->getName()));
                }

                $returnType = $method->getReturnType();
                if (!$returnType instanceof \ReflectionNamedType || Configuration::class !== $returnType->getName()) {
                    throw new \InvalidArgumentException(\sprintf('The method "%s::%s()" annotated with #[AsNativeConfiguration] must have a return type of "%s".', $class, $method->getName(), Configuration::class));
                }

                foreach ($attributes as $attribute) {
                    $asNativeConfiguration = $attribute->newInstance();

                    $childDefinition = new ChildDefinition($serviceId);
                    $childDefinition->setClass(Configuration::class);
                    $childDefinition->setFactory([new Reference($serviceId), $method->getName()]);
                    $childDefinition->setShared(false);
                    $childDefinition->addTag(self::TAG, ['path' => $asNativeConfiguration->path]);

                    $childServiceId = \sprintf('.ux_native.configuration.%s.%s', $reflectionClass->getShortName(), $method->getName());
                    $container->setDefinition($childServiceId, $childDefinition);
                }
            }
        }
    }
}
