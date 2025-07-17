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

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\Autocomplete\AutocompleteResultsExecutor;
use Symfony\UX\Autocomplete\AutocompleterRegistry;
use Symfony\UX\Autocomplete\Checksum\ChecksumCalculator;
use Symfony\UX\Autocomplete\Controller\EntityAutocompleteController;
use Symfony\UX\Autocomplete\Doctrine\DoctrineRegistryWrapper;
use Symfony\UX\Autocomplete\Doctrine\EntityMetadataFactory;
use Symfony\UX\Autocomplete\Doctrine\EntitySearchUtil;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\AutocompleteChoiceTypeExtension;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;
use Symfony\UX\Autocomplete\Form\ParentEntityAutocompleteType;
use Symfony\UX\Autocomplete\Form\WrappedEntityTypeAutocompleter;
use Symfony\UX\Autocomplete\Maker\MakeAutocompleteField;

use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class AutocompleteExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['TwigBundle'])) {
            $container->prependExtensionConfig('twig', [
                'form_themes' => ['@Autocomplete/autocomplete_form_theme.html.twig'],
            ]);
        }

        if ($this->isAssetMapperAvailable($container)) {
            $container->prependExtensionConfig('framework', [
                'asset_mapper' => [
                    'paths' => [
                        __DIR__.'/../../assets/dist' => '@symfony/ux-autocomplete',
                    ],
                ],
            ]);
        }
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $this->registerBasicServices($container);
        if (ContainerBuilder::willBeAvailable('symfony/form', Form::class, ['symfony/framework-bundle'])) {
            $this->registerFormServices($container);
        }
    }

    private function registerBasicServices(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(AsEntityAutocompleteField::class, function (Definition $definition) {
            $definition->addTag(AutocompleteFormTypePass::ENTITY_AUTOCOMPLETE_FIELD_TAG);
        });

        $container
            ->register('ux.autocomplete.autocompleter_registry', AutocompleterRegistry::class)
            ->setArguments([
                abstract_arg('autocompleter service locator'),
            ]);

        $container
            ->register('ux.autocomplete.doctrine_registry_wrapper', DoctrineRegistryWrapper::class)
            ->setArguments([
                new Reference('doctrine', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ])
        ;

        $container
            ->register('ux.autocomplete.results_executor', AutocompleteResultsExecutor::class)
            ->setArguments([
                new Reference('ux.autocomplete.doctrine_registry_wrapper'),
                new Reference('property_accessor'),
                new Reference('security.helper', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
        ;

        $container
            ->register('ux.autocomplete.entity_search_util', EntitySearchUtil::class)
            ->setArguments([
                new Reference('ux.autocomplete.entity_metadata_factory'),
            ])
        ;

        $container
            ->register('ux.autocomplete.entity_metadata_factory', EntityMetadataFactory::class)
            ->setArguments([
                new Reference('ux.autocomplete.doctrine_registry_wrapper'),
            ])
        ;

        $container
            ->register('ux.autocomplete.entity_autocomplete_controller', EntityAutocompleteController::class)
            ->setArguments([
                new Reference('ux.autocomplete.autocompleter_registry'),
                new Reference('ux.autocomplete.results_executor'),
                new Reference('router'),
                new Reference('ux.autocomplete.checksum_calculator'),
            ])
            ->addTag('controller.service_arguments')
        ;

        $container
            ->register('ux.autocomplete.make_autocomplete_field', MakeAutocompleteField::class)
            ->setArguments([
                new Reference('maker.doctrine_helper', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ])
            ->addTag('maker.command')
        ;

        $container
            ->register('ux.autocomplete.checksum_calculator', ChecksumCalculator::class)
            ->setArguments([
                '%kernel.secret%',
            ])
        ;
    }

    private function registerFormServices(ContainerBuilder $container): void
    {
        $container
            ->register('ux.autocomplete.base_entity_type', BaseEntityAutocompleteType::class)
            ->setArguments([
                new Reference('router'),
            ])
            ->addTag('form.type');

        $container
            ->register('ux.autocomplete.entity_type', ParentEntityAutocompleteType::class)
            ->setDeprecated('symfony/ux-autocomplete', '2.13', 'The "%service_id%" form type is deprecated since 2.13. Use "ux.autocomplete.base_entity_type" instead.')
            ->setArguments([
                new Reference('router'),
            ])
            ->addTag('form.type');

        $container
            ->register('ux.autocomplete.choice_type_extension', AutocompleteChoiceTypeExtension::class)
            ->setArguments([
                new Reference('ux.autocomplete.checksum_calculator'),
                new Reference('translator', ContainerInterface::IGNORE_ON_INVALID_REFERENCE),
            ])
            ->addTag('form.type_extension');

        $container
            ->register('ux.autocomplete.wrapped_entity_type_autocompleter', WrappedEntityTypeAutocompleter::class)
            ->setAbstract(true)
            ->setArguments([
                abstract_arg('form type string'),
                new Reference('form.factory'),
                new Reference('ux.autocomplete.entity_metadata_factory'),
                new Reference('property_accessor'),
                new Reference('ux.autocomplete.entity_search_util'),
            ]);
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }
}
