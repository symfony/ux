<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\LazyChoiceLoader;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * All form types that want to expose autocomplete functionality should use this for its getParent().
 */
final class BaseEntityAutocompleteType extends AbstractType
{
    use AutocompleteTypeTrait;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setAttribute('autocomplete_url', $this->resolveAutocompleteUrl($builder, $options, AsEntityAutocompleteField::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choiceLoader = static function (Options $options, $loader) {
            if (null === $loader) {
                return null;
            }

            if (!class_exists(LazyChoiceLoader::class)) {
                throw new \LogicException(\sprintf('Using "%s" with "%s" requires symfony/form >= 7.2 to be installed. Try running "composer require symfony/form:>=7.2".', LazyChoiceLoader::class, __CLASS__));
            }

            return new LazyChoiceLoader($loader);
        };

        $resolver->setDefaults([
            'autocomplete' => true,
            'choice_loader' => $choiceLoader,
            // set to the fields to search on or null to search on all fields
            'searchable_fields' => null,
            // override the search logic - set to a callable:
            // function(QueryBuilder $qb, string $query, EntityRepository $repository) {
            //     $qb->andWhere('entity.name LIKE :filter OR entity.description LIKE :filter')
            //         ->setParameter('filter', '%'.$query.'%');
            // }
            'filter_query' => null,
            // set to the string role that's required to view the autocomplete results
            // or a callable: function(Symfony\Component\Security\Core\Security $security): bool
            'security' => false,
            // set the max results number that a query on automatic endpoint return.
            'max_results' => 10,
            // extra attributes to add to the autocomplete result, either an array or a callable (called with the entity)
            'additional_attributes' => null,
        ]);

        $resolver->setAllowedTypes('security', ['boolean', 'string', 'callable']);
        $resolver->setAllowedTypes('max_results', ['int', 'null']);
        $resolver->setAllowedTypes('filter_query', ['callable', 'null']);
        $resolver->setAllowedTypes('additional_attributes', ['null', 'callable', 'array']);
        $resolver->setNormalizer('searchable_fields', static function (Options $options, ?array $searchableFields) {
            if (null !== $searchableFields && null !== $options['filter_query']) {
                throw new RuntimeException('Both the searchable_fields and filter_query options cannot be set.');
            }

            return $searchableFields;
        });
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'ux_entity_autocomplete';
    }

    private function getUrlGenerator(): UrlGeneratorInterface
    {
        return $this->urlGenerator;
    }
}
