<?php

namespace Symfony\UX\Collection\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollectionTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [CollectionType::class];
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var FormInterface|null $prototype */
        $prototype = $builder->getAttribute('prototype');

        if (!$prototype) {
            return;
        }

        // TODO add button only if `delete_type` is defined and set `delete_type` default to null?
        if ($options['allow_delete']) {
            // add delete button to prototype
            // TODO add toolbar here to allow extension add other buttons
            $prototype->add('deleteButton', $options['delete_type'], $options['delete_options']);
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var FormInterface|null $prototype */
        $prototype = $form->getConfig()->getAttribute('prototype');

        if (!$prototype) {
            return;
        }

        if ($options['allow_delete']) {
            // add delete button to rendered elements from the Collection ResizeListener
            foreach ($form as $child) {
                $child->add('deleteButton', $options['delete_type'], $options['delete_options']);
            }
        }

        // TODO add button only if `add_type` is defined and set `add_type` default to null?
        if ($options['allow_add']) {
            // TODO add toolbar here to allow extension add other buttons
            $form->add('addButton', $options['add_type'], $options['add_options']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $attrNormalizer = function (Options $options, $value) {
            if (!isset($value['data-controller'])) {
                // TODO default be `symfony--ux-collection--collection` or `collection`?
                $value['data-controller'] = 'symfony--ux-collection--collection';
            }

            $value['data-' . $value['data-controller'] . '-prototype-name-value'] = $options['prototype_name'];

            return $value;
        };

        $resolver->setDefaults([
            'add_type' => ButtonType::class,
            'add_options' => [],
            'delete_type' => ButtonType::class,
            'delete_options' => [],
        ]);

        $addOptionsNormalizer = function (Options $options, $value) {
            $value['block_name'] = 'add_button';
            $value['attr'] = \array_merge([
                'data-action' => $options['attr']['data-controller'] . '#add',
            ], $value['attr'] ?? []);

            return $value;
        };

        $deleteOptionsNormalizer = function (Options $options, $value) {
            $value['block_name'] = 'delete_button';
            $value['attr'] = \array_merge([
                'data-action' => $options['attr']['data-controller'] . '#delete',
            ], $value['attr'] ?? []);

            return $value;
        };

        $entryOptionsNormalizer = function (Options $options, $value) {
            $value['row_attr']['data-' . $options['attr']['data-controller'] . '-target'] = 'entry';

            return $value;
        };

        $resolver->setNormalizer('attr', $attrNormalizer);
        $resolver->setNormalizer('add_options', $addOptionsNormalizer);
        $resolver->setNormalizer('delete_options', $deleteOptionsNormalizer);
        $resolver->addNormalizer('entry_options', $entryOptionsNormalizer);
    }
}
