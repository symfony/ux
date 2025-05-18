<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Dropzone\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 */
class DropzoneType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'attr' => [
                'placeholder' => 'Drag and drop or browse',
            ],
        ]);

        $resolver->setDefault('preview', function (OptionsResolver $previewResolver): void {
            $previewResolver->setDefaults([
                'style' => 'legacy',
                'can_open_file_picker' => true,
                'can_toggle_placeholder' => true,
            ])
            ->addAllowedTypes('style', 'string')
            ->addAllowedTypes('can_open_file_picker', 'bool')
            ->addAllowedTypes('can_toggle_placeholder', ['bool', 'string'])
            ->setAllowedValues('style', ['legacy', 'block', 'inline'])
            ->setAllowedValues('can_toggle_placeholder', ['auto', true, false]);
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['preview'] = $options['preview'];
        $view->vars['controller_options'] = [
            'preview' => $options['preview'],
        ];
    }

    public function getParent()
    {
        return FileType::class;
    }

    public function getBlockPrefix()
    {
        return 'dropzone';
    }
}
