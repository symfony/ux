<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class SendNotificationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('message', ChoiceType::class, [
                'placeholder' => 'Choose a message',
                'label' => false,
                'help' => 'This will be sent to ANY user on this page as a browser notification! (<small>some browsers do not support the Notification API</small>)',
                'help_html' => true,
                'choices' => array_flip(self::getTextChoices()),
                'constraints' => [
                    new NotBlank(),
                ],
            ]);
    }

    public static function getTextChoices(): array
    {
        return [
            'Hey! I\', sending notifications via UX Notify!',
            'All penguin-inspired tuxedos are now on sale! 🐧',
            'Today is a great day to visit the zoo!',
            'Attention! I am a browser notification.',
        ];
    }
}
