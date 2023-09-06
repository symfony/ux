<?php

namespace App\Form;

use App\Enum\Food;
use App\Enum\Meal;
use App\Enum\PizzaSize;
use App\Model\MealPlan;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MealPlannerForm extends AbstractType
{
    private FormFactoryInterface $factory;

    /**
     * @var array<string, mixed>
     */
    private $dependencies = [];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->factory = $builder->getFormFactory();

        $builder->add('meal', EnumType::class, [
            'class' => Meal::class,
            'choice_label' => fn (Meal $meal): string => $meal->getReadable(),
            'placeholder' => 'Which meal is it?',
            'autocomplete' => true,
        ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);

        $builder->get('meal')->addEventListener(FormEvents::POST_SUBMIT, [$this, 'storeDependencies']);
        $builder->get('meal')->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmitMeal']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MealPlan::class]);
    }

    public function onPreSetData(FormEvent $event): void
    {
        // the object tied to your form
        /** @var ?MealPlan $data */
        $data = $event->getData();

        $this->addFoodField($event->getForm(), $data?->getMeal(), $data?->getFood());
        $this->addPizzaSizeField($event->getForm(), $data?->getPizzaSize());
    }

    public function onPostSubmit(FormEvent $event): void
    {
        $this->dependencies = [];
    }

    public function storeDependencies(FormEvent $event): void
    {
        $this->dependencies[$event->getForm()->getName()] = $event->getForm()->getData();
    }

    public function onPostSubmitMeal(FormEvent $event): void
    {
        $this->addFoodField(
            $event->getForm()->getParent(),
            $this->dependencies['meal'],
        );
    }

    public function onPostSubmitFood(FormEvent $event): void
    {
        $this->addPizzaSizeField(
            $event->getForm()->getParent(),
            $this->dependencies['mainFood'],
        );
    }

    public function addFoodField(FormInterface $form, ?Meal $meal, ?Food $food = null): void
    {
        $mainFood = $this->factory
            ->createNamedBuilder('mainFood', EnumType::class, $food, [
                'class' => Food::class,
                'placeholder' => null === $meal ? 'Select a meal first' : sprintf('What\'s for %s?', $meal->getReadable()),
                'choices' => $meal?->getFoodChoices(),
                'choice_label' => fn (Food $food): string => $food->getReadable(),
                'disabled' => null === $meal,
                // silence real-time "invalid" message when switching "meals"
                'invalid_message' => false,
                'autocomplete' => true,
                'auto_initialize' => false,
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'storeDependencies'])
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmitFood']);

        $form->add($mainFood->getForm());
    }

    public function addPizzaSizeField(FormInterface $form, ?Food $food): void
    {
        if (Food::Pizza !== $food) {
            return;
        }

        $form->add('pizzaSize', EnumType::class, [
            'class' => PizzaSize::class,
            'placeholder' => 'What size pizza?',
            'choice_label' => fn (PizzaSize $pizzaSize): string => $pizzaSize->getReadable(),
            'required' => true,
            'autocomplete' => true,
        ]);
    }
}
