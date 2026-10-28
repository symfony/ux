How to Handle Multiple Submit Buttons with Turbo Drive
======================================================

When your form contains more than one submit button and you want to check
which of the buttons was clicked to adapt the program flow in your controller,
you need to add a value to each button because Turbo Drive 7.1 doesn't send
the clicked button when it has no ``value`` attribute::

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

.. note::

    This is only needed with Turbo 7.1. Starting from Turbo 7.2, the clicked
    button is always sent, and the ``isClicked()`` method of each button works
    without adding any value.
