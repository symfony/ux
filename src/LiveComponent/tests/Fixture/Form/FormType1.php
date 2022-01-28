<?php
declare(strict_types=1);

namespace Symfony\UX\LiveComponent\Tests\Fixture\Form;

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

class FormType1 extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('text', TextType::class)
            ->add('textarea', TextareaType::class)
            ->add('range', RangeType::class)
            ->add('choice', ChoiceType::class, [
                'choices' => [
                    'foo' => 1,
                    'bar' => 2,
                ],
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
                'data' => [2]
            ])
            ->add('checkbox', CheckboxType::class)
            ->add('checkbox_checked', CheckboxType::class, [
                'data' => true
            ])
            ->add('file', FileType::class)
            ->add('hidden', HiddenType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_protection' => false
        ]);
    }
}
