<?php

namespace Symfony\UX\FormCollection\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
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

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $form->add('toolbar', UXCollectionToolbarType::class, [
            'allow_add' => $options['allow_add'],
            'add_options' => $options['add_options'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $addOptionsNormalizer = function (Options $options, $value) {
            $value['block_name'] = 'add_button';
            $value['attr'] = array_merge([
                'data-collection-target' => 'addButton',
                'data-action' => 'collection#add',
            ], $value['attr'] ?? []);

            return $value;
        };

        $deleteOptionsNormalizer = function (Options $options, $value) {
            $value['block_name'] = 'delete_button';
            $value['attr'] = array_merge([
                'data-collection-target' => 'deleteButton',
                'data-action' => 'collection#delete',
            ], $value['attr'] ?? []);

            return $value;
        };

        $attrNormalizer = function (Options $options, $value) {
            $value['data-controller'] = 'collection';

            return $value;
        };

        $entryTypeNormalizer = function (OptionsResolver $options, $value) {
            return UXCollectionEntryType::class;
        };

        $entryOptionsNormalizer = function (OptionsResolver $options, $value) {
            $value['row_attr']['data-controller-target'] = 'entry';

            return [
                'row_attr' => [
                    'data-controller-target' => 'entry',
                ],
                'allow_delete' => $options['allow_delete'],
                'delete_options' => $options['delete_options'],
                'entry_type' => $options['ux_entry_type'] ?? null,
                'entry_options' => $value,
            ];
        };

        $resolver->setDefaults([
            'ux_entry_type' => TextType::class,
            'add_options' => [],
            'delete_options' => [],
            'original_entry_type' => [],
        ]);

        $resolver->setNormalizer('add_options', $addOptionsNormalizer);
        $resolver->setNormalizer('delete_options', $deleteOptionsNormalizer);
        $resolver->setNormalizer('attr', $attrNormalizer);
        $resolver->addNormalizer('entry_type', $entryTypeNormalizer);
        $resolver->addNormalizer('entry_options', $entryOptionsNormalizer);
    }
}
