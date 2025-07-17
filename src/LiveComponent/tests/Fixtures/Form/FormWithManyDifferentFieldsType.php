<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\UX\LiveComponent\Tests\Fixtures\Entity\CategoryFixtureEntity;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class FormWithManyDifferentFieldsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('text', TextType::class)
            ->add('textarea', TextareaType::class, [
                'constraints' => [new Length(max: 5, maxMessage: 'textarea is too long')]
            ])
            ->add('range', RangeType::class)
            ->add('choice', ChoiceType::class, [
                'choices' => [
                    'foo' => 1,
                    'bar' => 2,
                ],
                'required' => false,
            ])
            ->add('choice_required_with_placeholder', ChoiceType::class, [
                'choices' => [
                    'bar' => 2,
                    'foo' => 1,
                ],
                'placeholder' => 'foo',
            ])
            ->add('choice_required_with_empty_placeholder', ChoiceType::class, [
                'choices' => [
                    'bar' => 2,
                    'foo' => 1,
                ],
                'placeholder' => '',
            ])
            ->add('choice_required_without_placeholder', ChoiceType::class, [
                'choices' => [
                    'bar' => 2,
                    'foo' => 1,
                ],
            ])
            ->add('choice_required_without_placeholder_and_choice_group', ChoiceType::class, [
                'choices' => [
                    'Bar Group' => [
                        'Bar Label' => 'ok',
                        'Foo Label' => 'foo_value',
                    ],
                    'foo' => 1,
                ],
            ])
            ->add('choice_required_with_preferred_choices_array', ChoiceType::class, [
                'choices' => [
                    'Bar Group' => [
                        'Bar Label' => 'ok',
                        'Foo Label' => 'foo_value',
                    ],
                    'foo' => 1,
                ],
                'preferred_choices' => ['foo_value'],
            ])
            ->add('choice_required_with_preferred_choices_callback', ChoiceType::class, [
                'choices' => [
                    'Bar Group' => [
                        'Bar Label' => 'ok',
                        'Foo Label' => 'foo_value',
                    ],
                    'foo' => 1,
                ],
                'preferred_choices' => function ($choice): bool {
                    return is_int($choice);
                },
            ])
            ->add('choice_required_with_empty_preferred_choices', ChoiceType::class, [
                'choices' => [
                    'Bar Group' => [
                        'Bar Label' => 'ok',
                        'Foo Label' => 'foo_value',
                    ],
                    'foo' => 1,
                ],
                'preferred_choices' => [],
            ])
            ->add('choice_expanded', ChoiceType::class, [
                'choices' => [
                    'foo' => 1,
                    'bar' => 2,
                ],
                'expanded' => true,
            ])
            ->add('choice_multiple', ChoiceType::class, [
                'choices' => [
                    'foo' => 1,
                    'bar' => 2,
                ],
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('select_multiple', ChoiceType::class, [
                'choices' => [
                    'foo' => 1,
                    'bar' => 2,
                ],
                'multiple' => true,
            ])
            ->add('entity', EntityType::class, [
                'class' => CategoryFixtureEntity::class,
                'choice_label' => 'name',
            ])
            ->add('checkbox', CheckboxType::class)
            ->add('checkbox_checked', CheckboxType::class)
            ->add('file', FileType::class)
            ->add('hidden', HiddenType::class)
            ->add('complexType', ComplexFieldType::class)
        ;
    }
}
