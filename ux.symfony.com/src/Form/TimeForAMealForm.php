<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TimeForAMealForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('foods', FoodAutocompleteField::class)
            ->add('name', TextType::class, [
                'label' => 'What should we call this meal?',
            ])
        ;
    }
}
