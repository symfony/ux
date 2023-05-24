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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * All form types that want to expose autocomplete functionality should use this for its getParent().
 */
final class ParentEntityAutocompleteType extends AbstractType implements DataMapperInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $formType = $builder->getType()->getInnerType();
        $attribute = AsEntityAutocompleteField::getInstance($formType::class);

        if (!$attribute && empty($options['autocomplete_url'])) {
            throw new \LogicException(sprintf('You must either provide your own autocomplete_url, or add #[AsEntityAutocompleteField] attribute to %s.', $formType::class));
        }

        // Use the provided URL, or auto-generate from the provided alias
        $autocompleteUrl = $options['autocomplete_url'] ?? $this->urlGenerator->generate($attribute->getRoute(), [
            'alias' => $attribute->getAlias() ?: AsEntityAutocompleteField::shortName($formType::class),
        ]);

        $builder
            ->addEventSubscriber(new AutocompleteEntityTypeSubscriber($autocompleteUrl))
            ->setDataMapper($this);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        // Add a custom block prefix to inner field to ease theming:
        array_splice($view['autocomplete']->vars['block_prefixes'], -1, 0, 'ux_entity_autocomplete_inner');
        // this IS A compound (i.e. has children) field
        // however, we only render the child "autocomplete" field. So for rendering, fake NOT compound
        $view->vars['compound'] = false;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'multiple' => false,
            // force display errors on this form field
            'error_bubbling' => false,
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

        $resolver->setRequired(['class']);
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

    public function getBlockPrefix(): string
    {
        return 'ux_entity_autocomplete';
    }

    public function mapDataToForms($data, $forms)
    {
        $form = current(iterator_to_array($forms, false));
        $form->setData($data);
    }

    public function mapFormsToData($forms, &$data)
    {
        $form = current(iterator_to_array($forms, false));
        $data = $form->getData();
    }
}
