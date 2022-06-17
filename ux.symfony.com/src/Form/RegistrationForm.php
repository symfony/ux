<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank(message: 'Please enter an email!'),
                    new Email(),
                ],
                'help' => 'Type an invalid email and watch as it auto-validates when you leave the field!',
            ])
            ->add('password', PasswordType::class, [
                'constraints' => [
                    new NotBlank(message: 'Please enter a password'),
                    new Length(min: 5, minMessage: 'Surely you can think of something longer than that!'),
                ],
                'always_empty' => false,
            ])
            ->add('terms', CheckboxType::class, [
                'label' => 'Agree to terms',
                'constraints' => [
                    new NotBlank(message: 'Please agree to the non-existent terms'),
                ],
            ])
        ;
    }
}
