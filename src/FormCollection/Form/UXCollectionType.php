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
use Symfony\Component\OptionsResolver\Options;
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
        $defaultButtonAddOptions = [
            'label' => 'Add',
            'class' => '',
        ];
        $defaultButtonDeleteOptions = [
            'label' => 'Remove',
            'class' => '',
        ];
        $resolver->setDefaults([
            'button_add_options' => $defaultButtonAddOptions,
            'button_delete_options' => $defaultButtonDeleteOptions,
        ]);

        $resolver->setAllowedTypes('button_add_options', 'array');
        $resolver->setAllowedTypes('button_delete_options', 'array');

        $resolver->setNormalizer('button_add_options', function (Options $options, $value) use ($defaultButtonAddOptions) {
            $value['label'] = $value['label'] ?? $defaultButtonAddOptions['label'];
            $value['class'] = $value['class'] ?? $defaultButtonAddOptions['class'];

            return $value;
        });
        $resolver->setNormalizer('button_delete_options', function (Options $options, $value) use ($defaultButtonDeleteOptions) {
            $value['label'] = $value['label'] ?? $defaultButtonDeleteOptions['label'];
            $value['class'] = $value['class'] ?? $defaultButtonDeleteOptions['class'];

            return $value;
        });
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);

        $view->vars['button_add_options'] = $options['button_add_options'];
        $view->vars['button_delete_options'] = $options['button_delete_options'];
        $view->vars['prototype_name'] = $options['prototype_name'];
    }

    public function getBlockPrefix()
    {
        return 'ux_collection';
    }
}
