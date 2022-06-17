<?php

namespace App\Form;

use App\Model\MealPlan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MealPlannerForm extends AbstractType
{
    public const MEAL_BREAKFAST = 'breakfast';
    public const MEAL_SECOND_BREAKFAST = 'second breakfast';
    public const MEAL_ELEVENSES = 'elevenses';
    public const MEAL_LUNCH = 'lunch';
    public const MEAL_DINNER = 'dinner';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = [
            'Breakfast' => self::MEAL_BREAKFAST,
            'Second Breakfast' => self::MEAL_SECOND_BREAKFAST,
            'Elevenses' => self::MEAL_ELEVENSES,
            'Lunch' => self::MEAL_LUNCH,
            'Dinner' => self::MEAL_DINNER,
        ];
        $builder->add('meal', ChoiceType::class, [
            'choices' => $choices,
            'placeholder' => 'Which meal is it?',
        ]);

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                // the object tied to your form
                /** @var ?MealPlan $data */
                $data = $event->getData();

                $meal = $data?->getMeal();
                $this->addFoodField($event->getForm(), $meal);
            }
        );

        $builder->get('meal')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                // It's important here to fetch $event->getForm()->getData(), as
                // $event->getData() will get you the client data (that is, the ID)
                $meal = $event->getForm()->getData();

                // since we've added the listener to the child, we'll have to pass on
                // the parent to the callback functions!
                $this->addFoodField($event->getForm()->getParent(), $meal);
            }
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => MealPlan::class]);
    }

    private function getAvailableFoodChoices(string $meal): array
    {
        $foods = match ($meal) {
            self::MEAL_BREAKFAST => ['Eggs 🍳', 'Bacon 🥓', 'Strawberries 🍓', 'Croissant 🥐'],
            self::MEAL_SECOND_BREAKFAST => ['Bagel 🥯', 'Kiwi 🥝', 'Avocado 🥑', ' Wafles 🧇'],
            self::MEAL_ELEVENSES => ['Pancakes 🥞', 'Salad 🥙', 'Tea ☕️'],
            self::MEAL_LUNCH => ['Sandwich 🥪', 'Cheese 🧀', 'Sushi 🍱'],
            self::MEAL_DINNER => ['Pizza 🍕', 'A Pint 🍺', 'Pasta 🍝'],
        };

        $foods = array_combine($foods, $foods);

        return $foods;
    }

    public function addFoodField(FormInterface $form, ?string $meal)
    {
        $foodChoices = null === $meal ? [] : $this->getAvailableFoodChoices($meal);

        $form->add('mainFood', ChoiceType::class, [
            'placeholder' => null === $meal ? 'Select a meal first' : sprintf('What\'s for %s?', $meal),
            'choices' => $foodChoices,
            'disabled' => null === $meal,
            // silence real-time "invalid" message when switching "meals"
            'invalid_message' => false,
        ]);
    }
}
