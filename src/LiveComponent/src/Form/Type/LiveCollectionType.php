<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Gábor Egyed <gabor.egyed@gmail.com>
 *
 * @experimental
 */
final class LiveCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['allow_add']) {
            $prototype = $builder->create('add', $options['button_add_type'], $options['button_add_options']);
            $builder->setAttribute('button_add_prototype', $prototype->getForm());
        }

        if ($options['allow_delete']) {
            $prototype = $builder->create('delete', $options['button_delete_type'], $options['button_delete_options']);
            $builder->setAttribute('button_delete_prototype', $prototype->getForm());
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if ($form->getConfig()->hasAttribute('button_add_prototype')) {
            $prototype = $form->getConfig()->getAttribute('button_add_prototype');
            $view->vars['button_add_prototype'] = $prototype->setParent($form)->createView($view);
            array_splice($view->vars['button_add_prototype']->vars['block_prefixes'], 1, 0, 'live_collection_button_add');
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $prefixOffset = -2;
        // check if the entry type also defines a block prefix
        /** @var FormInterface $entry */
        foreach ($form as $entry) {
            if ($entry->getConfig()->getOption('block_prefix')) {
                --$prefixOffset;
            }

            break;
        }

        foreach ($view as $entryView) {
            array_splice($entryView->vars['block_prefixes'], $prefixOffset, 0, 'live_collection_entry');
        }

        if ($form->getConfig()->hasAttribute('button_delete_prototype')) {
            $prototype = $form->getConfig()->getAttribute('button_delete_prototype');

            $prototypes = [];
            foreach ($form as $k => $entry) {
                $prototypes[$k] = clone $prototype;
                $prototypes[$k]->setParent($entry);
            }

            foreach ($view as $k => $entryView) {
                $entryView->vars['button_delete_prototype'] = $prototypes[$k]->createView($entryView);
                array_splice($entryView->vars['button_delete_prototype']->vars['block_prefixes'], 1, 0, 'live_collection_button_delete');
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'prototype' => false,
            'entry_options' => [
                'label' => false,
            ],
            'button_add_type' => ButtonType::class,
            'button_add_options' => [],
            'button_delete_type' => ButtonType::class,
            'button_delete_options' => [],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'live_collection';
    }

    public function getParent(): string
    {
        return CollectionType::class;
    }
}
