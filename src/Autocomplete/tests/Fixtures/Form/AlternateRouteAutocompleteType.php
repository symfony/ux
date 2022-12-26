<?php

namespace Symfony\UX\Autocomplete\Tests\Fixtures\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\ParentEntityAutocompleteType;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Ingredient;

#[AsEntityAutocompleteField(route: 'ux_autocomplete_alternate')]
class AlternateRouteAutocompleteType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'class' => Ingredient::class,
            'choice_label' => function(Ingredient $ingredient) {
                return $ingredient->getName();
            },
            'multiple' => true,
        ]);
    }

    public function getParent(): string
    {
        return ParentEntityAutocompleteType::class;
    }
}
