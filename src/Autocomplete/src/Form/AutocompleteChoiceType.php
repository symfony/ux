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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * All choice-based form types that want to expose autocomplete functionality should use this for its getParent().
 *
 * This is the non-Doctrine equivalent of BaseEntityAutocompleteType.
 */
final class AutocompleteChoiceType extends AbstractType
{
    use AutocompleteTypeTrait;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setAttribute('autocomplete_url', $this->resolveAutocompleteUrl($builder, $options, AsAutocompleteField::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'autocomplete' => true,
            // set to the string role that's required to view the autocomplete results
            // or a callable: function(Symfony\Bundle\SecurityBundle\Security $security): bool
            'security' => false,
            // set the max results number that a query on automatic endpoint return.
            'max_results' => 10,
        ]);

        $resolver->setAllowedTypes('security', ['boolean', 'string', 'callable']);
        $resolver->setAllowedTypes('max_results', ['int', 'null']);
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'ux_autocomplete_choice';
    }

    private function getUrlGenerator(): UrlGeneratorInterface
    {
        return $this->urlGenerator;
    }
}
