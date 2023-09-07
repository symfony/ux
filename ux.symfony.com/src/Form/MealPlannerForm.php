<?php

namespace App\Form;

use App\Enum\Food;
use App\Enum\Meal;
use App\Enum\PizzaSize;
use App\Model\MealPlan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class MealPlannerForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('meal', EnumType::class, [
                'class' => Meal::class,
                'choice_label' => fn (Meal $meal): string => $meal->getReadable(),
                'placeholder' => 'Which meal is it?',
                'autocomplete' => true,
            ])
            // see: https://github.com/SymfonyCasts/dynamic-forms
            ->addDependent('mainFood', 'meal', function (DependentField $field, ?Meal $meal) {
                $field->add(EnumType::class, [
                    'class' => Food::class,
                    'placeholder' => null === $meal ? 'Select a meal first' : sprintf('What\'s for %s?', $meal->getReadable()),
                    'choices' => $meal?->getFoodChoices(),
                    'choice_label' => fn (Food $food): string => $food->getReadable(),
                    'disabled' => null === $meal,
                    'autocomplete' => true,
                ]);
            })
            ->addDependent('pizzaSize', 'mainFood', function (DependentField $field, ?Food $food) {
                if (Food::Pizza !== $food) {
                    return;
                }

                $field->add(EnumType::class, [
                    'class' => PizzaSize::class,
                    'placeholder' => 'What size pizza?',
                    'choice_label' => fn (PizzaSize $pizzaSize): string => $pizzaSize->getReadable(),
                    'required' => true,
                    'autocomplete' => true,
                ]);
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MealPlan::class]);
    }
}
