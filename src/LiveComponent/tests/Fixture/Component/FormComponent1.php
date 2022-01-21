<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixture\Component;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\RangeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
#[AsLiveComponent('component6')]
class FormComponent1 extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    private FormBuilderInterface $builder;

    public function __construct(FormBuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    protected function instantiateForm(): FormInterface
    {
        $this->builder
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
            ->add('checkbox', CheckboxType::class)
            ->add('file', FileType::class)
            ->add('hidden', HiddenType::class)
        ;

        return $this->builder->getForm();
    }
}
