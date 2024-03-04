<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use App\Enum\Food;
use App\Enum\Meal;
use App\Enum\PizzaSize;
use App\Model\MealPlan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class MealPlannerForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /**
         * Install DynamicFormBuilder:.
         *
         *    composer require symfonycasts/dynamic-forms
         */
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('folder', ChoiceType::class, [
                'choices' => [
                    'Folder 1' => 'folder1',
                    'Folder 2' => 'folder2',
                ],
                'data' => 'folder1'
            ])
            // see: https://github.com/SymfonyCasts/dynamic-forms
            ->addDependent('domain', 'folder', function (DependentField $field, ?string $folder) {
                if ($folder === 'folder1') {
                    $host = 'domain1.com';
                } else {
                    $host = 'domain2.com';
                }

                $field->add(TextType::class, [
                    'mapped' => false,
                    'label' => 'Domain',
                    'required' => false,
                    'data' => $host, // don't work
                    'attr' => [
                        //'placeholder' => $host, // works
                    ],
                ]);
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        
    }
}
