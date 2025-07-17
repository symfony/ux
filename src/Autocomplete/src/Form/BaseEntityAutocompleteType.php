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
use Symfony\UX\Autocomplete\Form\ChoiceList\Loader\ExtraLazyChoiceLoader;

/**
 * All form types that want to expose autocomplete functionality should use this for its getParent().
 */
final class BaseEntityAutocompleteType extends AbstractType
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setAttribute('autocomplete_url', $this->getAutocompleteUrl($builder, $options));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choiceLoader = static function (Options $options, $loader) {
            if (null === $loader) {
                return null;
            }

            if (class_exists(LazyChoiceLoader::class)) {
                return new LazyChoiceLoader($loader);
            }

            return new ExtraLazyChoiceLoader($loader);
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
        ]);

        $resolver->setAllowedTypes('security', ['boolean', 'string', 'callable']);
        $resolver->setAllowedTypes('max_results', ['int', 'null']);
        $resolver->setAllowedTypes('filter_query', ['callable', 'null']);
        $resolver->setNormalizer('searchable_fields', function (Options $options, ?array $searchableFields) {
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

    /**
     * Uses the provided URL, or auto-generate from the provided alias.
     */
    private function getAutocompleteUrl(FormBuilderInterface $builder, array $options): string
    {
        if ($options['autocomplete_url']) {
            return $options['autocomplete_url'];
        }

        $formType = $builder->getType()->getInnerType();
        $attribute = AsEntityAutocompleteField::getInstance($formType::class);

        if (!$attribute) {
            throw new \LogicException(\sprintf('You must either provide your own autocomplete_url, or add #[AsEntityAutocompleteField] attribute to "%s".', $formType::class));
        }

        return $this->urlGenerator->generate($attribute->getRoute(), [
            'alias' => $attribute->getAlias() ?: AsEntityAutocompleteField::shortName($formType::class),
        ]);
    }
}
