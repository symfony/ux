<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\DependencyInjection;

use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\UX\Autocomplete\Form\AsAutocompleteField;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class AutocompleteFormTypePass implements CompilerPassInterface
{
    /** @var string Tag applied to form types that will be used for autocompletion */
    public const ENTITY_AUTOCOMPLETE_FIELD_TAG = 'ux.entity_autocomplete_field';
    /** @var string Tag applied to choice-based form types that will be used for autocompletion */
    public const AUTOCOMPLETE_FIELD_TAG = 'ux.autocomplete_field';
    /** @var string Tag applied to EntityAutocompleterInterface classes */
    public const ENTITY_AUTOCOMPLETER_TAG = 'ux.entity_autocompleter';
    /** @var string Tag applied to AutocompleterInterface classes */
    public const AUTOCOMPLETER_TAG = 'ux.autocompleter';

    public function process(ContainerBuilder $container): void
    {
        $this->processFieldTag($container, self::ENTITY_AUTOCOMPLETE_FIELD_TAG, 'ux.autocomplete.wrapped_entity_type_autocompleter', self::ENTITY_AUTOCOMPLETER_TAG, AsEntityAutocompleteField::class);
        $this->processFieldTag($container, self::AUTOCOMPLETE_FIELD_TAG, 'ux.autocomplete.wrapped_choice_type_autocompleter', self::AUTOCOMPLETER_TAG, AsAutocompleteField::class);
        $this->processAutocompleterTags($container);
    }

    /**
     * @param class-string<AsAutocompleteField> $attributeClass
     */
    private function processFieldTag(ContainerBuilder $container, string $tag, string $abstractServiceId, string $autocompleterTag, string $attributeClass): void
    {
        foreach ($container->findTaggedServiceIds($tag, true) as $serviceId => $tags) {
            $serviceDefinition = $container->getDefinition($serviceId);
            if (!$serviceDefinition->hasTag('form.type')) {
                throw new \LogicException(\sprintf('Service "%s" has the "%s" tag, but is not tagged with "form.type". Did you add the "%s" attribute to a class that is not a form type?', $serviceId, $tag, $attributeClass));
            }
            $alias = $this->resolveAlias($serviceId, $serviceDefinition, $tags, $tag, $attributeClass);

            $wrappedDefinition = (new ChildDefinition($abstractServiceId))
                // the "formType" string
                ->replaceArgument(0, $serviceDefinition->getClass())
                ->addTag($autocompleterTag, ['alias' => $alias])
                ->addTag('kernel.reset', ['method' => 'reset']);
            $container->setDefinition($abstractServiceId.'.'.$alias, $wrappedDefinition);
        }
    }

    /**
     * @param class-string<AsAutocompleteField> $attributeClass
     */
    private function resolveAlias(string $serviceId, Definition $serviceDefinition, array $tag, string $tagName, string $attributeClass): string
    {
        if ($tag[0]['alias'] ?? null) {
            return $tag[0]['alias'];
        }

        $class = $serviceDefinition->getClass();
        $attribute = $attributeClass::getInstance($class);
        if (null === $attribute) {
            throw new \LogicException(\sprintf('The service "%s" either needs to have the #[%s] attribute above its class or its "%s" tag needs an "alias" key.', $serviceId, $tagName, $attributeClass));
        }

        return $attribute->getAlias() ?: $attributeClass::shortName($class);
    }

    private function processAutocompleterTags(ContainerBuilder $container): void
    {
        $servicesMap = [];
        foreach ([self::ENTITY_AUTOCOMPLETER_TAG, self::AUTOCOMPLETER_TAG] as $tagName) {
            foreach ($container->findTaggedServiceIds($tagName, true) as $serviceId => $tag) {
                if (!isset($tag[0]['alias'])) {
                    throw new \LogicException(\sprintf('The "%s" tag of the "%s" service needs "alias" key.', $tagName, $serviceId));
                }

                $servicesMap[$tag[0]['alias']] = new Reference($serviceId);
            }
        }

        $definition = $container->findDefinition('ux.autocomplete.autocompleter_registry');
        $definition->setArgument(0, ServiceLocatorTagPass::register($container, $servicesMap));
    }
}
