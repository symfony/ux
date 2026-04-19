How to Handle Multiple Submit Buttons with Turbo Drive
======================================================

When your form contains more than one submit button and you want to check
which of the buttons was clicked to adapt the program flow in your controller,
you need to add a value to each button because Turbo Drive doesn't send
elements with an empty value::

    // src/Form/TaskType.php
    namespace App\Form;

    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\Extension\Core\Type\SubmitType;
    use Symfony\Component\Form\Extension\Core\Type\TextType;
    use Symfony\Component\Form\FormBuilderInterface;

    class TaskType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options): void
        {
            $builder
                // ...
                ->add('save', SubmitType::class, [
                    'label' => 'Create Task',
                    'attr' => [
                        'value' => 'create-task',
                    ],
                ])
                ->add('saveAndAdd', SubmitType::class, [
                    'label' => 'Save and Add',
                    'attr' => [
                        'value' => 'save-and-add',
                    ],
                ]);
        }
    }
