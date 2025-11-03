<?php

namespace App\Form\Type;

use App\Form\Model\ProductionDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class ProductionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Movie' => 'movie',
                    'Videogame' => 'videogame',
                ],
                'placeholder' => 'Select a type',
                'attr' => [
                    'data-test-id' => 'production-type',
                ],
            ])
            ->addDependent('movieSearch', ['type'], function (DependentField $field, ?string $type) {
                if ('movie' !== $type) {
                    return;
                }

                $field->add(MovieAutocompleteType::class, [
                    'label' => 'Search Movies',
                    'required' => false,
                ]);
            })
            ->addDependent('videogameSearch', ['type'], function (DependentField $field, ?string $type) {
                if ('videogame' !== $type) {
                    return;
                }

                $field->add(VideogameAutocompleteType::class, [
                    'label' => 'Search Videogames',
                    'required' => false,
                ]);
            })
            ->add('title', TextType::class, [
                'required' => false,
                'attr' => [
                    'data-test-id' => 'production-title',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductionDto::class,
        ]);
    }
}
