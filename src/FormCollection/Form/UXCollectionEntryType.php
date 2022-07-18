<?php

namespace Symfony\UX\FormCollection\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @final
 * @experimental
 *
 * @internal only for internal usage to add the toolbar to every entry of a collection
 */
class UXCollectionEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'entry',
            $options['entry_type'],
            $options['entry_options'],
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $form->add('toolbar', UXCollectionEntryToolbarType::class, [
            'allow_delete' => $options['allow_delete'],
            'delete_options' => $options['delete_options'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'entry_type' => TextType::class,
            'entry_options' => [],
            'label' => false,
            'allow_delete' => false,
            'delete_options' => [],
            'row_attr' => [
                'data-collection-target' => 'entry',
            ],
        ]);

        $entryOptionsNormalizer = function (OptionsResolver $options, $value) {
            $value['inherit_data'] = true;

            return $value;
        };

        $resolver->addNormalizer('entry_options', $entryOptionsNormalizer);
    }
}
