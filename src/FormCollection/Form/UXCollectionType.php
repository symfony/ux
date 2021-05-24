<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\FormCollection\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @final
 * @experimental
 */
class UXCollectionType extends AbstractType
{
    public function getParent()
    {
        return CollectionType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'button_add_text' => 'Add',
            'button_add_class' => '',
            'button_delete_text' => 'Remove',
            'button_delete_class' => '',
        ]);

        $resolver->setAllowedTypes('button_add_text', 'string');
        $resolver->setAllowedTypes('button_add_class', 'string');
        $resolver->setAllowedTypes('button_delete_text', 'string');
        $resolver->setAllowedTypes('button_delete_class', 'string');
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        $view->vars['button_add_text'] = $options['button_add_text'];
        $view->vars['button_add_class'] = $options['button_add_class'];
        $view->vars['button_delete_text'] = $options['button_delete_text'];
        $view->vars['button_delete_class'] = $options['button_delete_class'];
        $view->vars['prototype_name'] = $options['prototype_name'];
    }

    public function getBlockPrefix()
    {
        return 'ux_collection';
    }
}
