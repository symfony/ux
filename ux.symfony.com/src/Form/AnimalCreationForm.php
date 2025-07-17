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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AnimalCreationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $animals = ['🐑', '🦖', '🦄', '🐖'];
        $builder
            ->add('name', TextType::class, [
                'constraints' => [new NotBlank(message: 'Your animal deserves a name!')],
            ])
            ->add('animal', ChoiceType::class, [
                'choices' => ['Choose an animal' => ''] + array_combine($animals, $animals),
                'constraints' => [new NotBlank(message: 'Choose your animal!')],
            ]);
    }
}
