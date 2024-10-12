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
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class AutocompleteFormTypePass implements CompilerPassInterface
{
    /** @var string Tag applied to form types that will be used for autocompletion */
    public const ENTITY_AUTOCOMPLETE_FIELD_TAG = 'ux.entity_autocomplete_field';
    /** @var string Tag applied to EntityAutocompleterInterface classes */
    public const ENTITY_AUTOCOMPLETER_TAG = 'ux.entity_autocompleter';

    public function process(ContainerBuilder $container): void
    {
        $this->processEntityAutocompleteFieldTag($container);
        $this->processEntityAutocompleterTag($container);
    }

    private function processEntityAutocompleteFieldTag(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds(self::ENTITY_AUTOCOMPLETE_FIELD_TAG, true) as $serviceId => $tag) {
            $serviceDefinition = $container->getDefinition($serviceId);
            if (!$serviceDefinition->hasTag('form.type')) {
                throw new \LogicException(\sprintf('Service "%s" has the "%s" tag, but is not tagged with "form.type". Did you add the "%s" attribute to a class that is not a form type?', $serviceId, self::ENTITY_AUTOCOMPLETE_FIELD_TAG, AsEntityAutocompleteField::class));
            }
            $alias = $this->getAlias($serviceId, $serviceDefinition, $tag);

            $wrappedDefinition = (new ChildDefinition('ux.autocomplete.wrapped_entity_type_autocompleter'))
                // the "formType" string
                ->replaceArgument(0, $serviceDefinition->getClass())
                ->addTag(self::ENTITY_AUTOCOMPLETER_TAG, ['alias' => $alias])
                ->addTag('kernel.reset', ['method' => 'reset']);
            $container->setDefinition('ux.autocomplete.wrapped_entity_type_autocompleter.'.$alias, $wrappedDefinition);
        }
    }

    private function getAlias(string $serviceId, Definition $serviceDefinition, array $tag): string
    {
        if ($tag[0]['alias'] ?? null) {
            return $tag[0]['alias'];
        }

        $class = $serviceDefinition->getClass();
        $attribute = AsEntityAutocompleteField::getInstance($class);
        if (null === $attribute) {
            throw new \LogicException(\sprintf('The service "%s" either needs to have the #[%s] attribute above its class or its "%s" tag needs an "alias" key.', $serviceId, self::ENTITY_AUTOCOMPLETE_FIELD_TAG, AsEntityAutocompleteField::class));
        }

        return $attribute->getAlias() ?: AsEntityAutocompleteField::shortName($class);
    }

    private function processEntityAutocompleterTag(ContainerBuilder $container): void
    {
        $servicesMap = [];
        foreach ($container->findTaggedServiceIds(self::ENTITY_AUTOCOMPLETER_TAG, true) as $serviceId => $tag) {
            if (!isset($tag[0]['alias'])) {
                throw new \LogicException(\sprintf('The "%s" tag of the "%s" service needs "alias" key.', self::ENTITY_AUTOCOMPLETER_TAG, $serviceId));
            }

            $servicesMap[$tag[0]['alias']] = new Reference($serviceId);
        }

        $definition = $container->findDefinition('ux.autocomplete.autocompleter_registry');
        $definition->setArgument(0, ServiceLocatorTagPass::register($container, $servicesMap));
    }
}
