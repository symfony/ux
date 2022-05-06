<?php

namespace Symfony\UX\Autocomplete\Tests\Fixtures\Form;

use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', CategoryAutocompleteType::class)
            ->add('portionSize', ChoiceType::class, [
                'choices' => [
                    'extra small <span>🥨</span>' => 'xs',
                    'small' => 's',
                    'medium' => 'm',
                    'large' => 'l',
                    'extra large' => 'xl',
                    'all you can eat' => '∞',
                ],
                'options_as_html' => true,
                'autocomplete' => true,
                'mapped' => false,
            ])
            ->add('tags', TextType::class, [
                'mapped' => false,
                'autocomplete' => true,
                'tom_select_options' => [
                    'create' => true,
                    'createOnBlur' => true,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
            'csrf_protection' => false,
        ]);
    }
}
