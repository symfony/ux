<?php

namespace Symfony\UX\FormCollection\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @final
 * @experimental
 */
class UXCollectionEntryToolbarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['allow_delete']) {
            $builder->add(
                'uxCollectionEntryDeleteButton',
                ButtonType::class,
                $options['delete_options'],
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'allow_delete' => false,
            'delete_options' => [],
        ]);
    }
}
