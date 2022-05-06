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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
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
        $attribute = AsEntityAutocompleteField::getInstance(\get_class($formType));
        if (!$attribute) {
            throw new \LogicException(sprintf('The %s class must have a #[AsEntityAutocompleteField] attribute above its class.', \get_class($formType)));
        }

        $autocompleteUrl = $this->urlGenerator->generate('ux_entity_autocomplete', [
            'alias' => $attribute->getAlias() ?: AsEntityAutocompleteField::shortName(\get_class($formType)),
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
            'searchable_fields' => null,
            // set to the string role that's required to view the autocomplete results
            // or a callable: function(Symfony\Component\Security\Core\Security $security): bool
            'security' => false,
        ]);

        $resolver->setRequired(['class']);
        $resolver->setAllowedTypes('security', ['boolean', 'string', 'callable']);
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
