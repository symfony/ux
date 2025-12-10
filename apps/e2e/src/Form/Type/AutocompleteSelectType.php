<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class AutocompleteSelectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'favorite_fruit',
            ChoiceType::class,
            [
                'choices' => [
                    'Apple' => 'apple',
                    'Banana' => 'banana',
                    'Cherry' => 'cherry',
                    'Coconut' => 'coconut',
                    'Grape' => 'grape',
                    'Kiwi' => 'kiwi',
                    'Lemon' => 'lemon',
                    'Mango' => 'mango',
                    'Orange' => 'orange',
                    'Papaya' => 'papaya',
                    'Peach' => 'peach',
                    'Pineapple' => 'pineapple',
                    'Pear' => 'pear',
                    'Pomegranate' => 'pomegranate',
                    'Pomelo' => 'pomelo',
                    'Raspberry' => 'raspberry',
                    'Strawberry' => 'strawberry',
                    'Watermelon' => 'watermelon',
                ],
                'autocomplete' => true,
                'label' => 'Your favorite fruit:'
            ]
        );
    }
}
