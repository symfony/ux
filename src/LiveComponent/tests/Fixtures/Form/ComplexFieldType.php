<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ComplexFieldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('sub_field', TextType::class);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        // try to confuse ComponentWithFormTrait
        // mimics what autocomplete does
        $view->vars['compound'] = false;
        $view->vars['compound_data'] = true;
    }
}
