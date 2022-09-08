<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Cropperjs\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Cropperjs\Model\Crop;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 *
 * @experimental
 */
class CropperType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('options', HiddenType::class, [
                'error_bubbling' => true,
                'attr' => [
                    'data-controller' => trim(($options['attr']['data-controller'] ?? '').' symfony--ux-cropperjs--cropper'),
                    'data-symfony--ux-cropperjs--cropper-public-url-value' => $options['public_url'],
                    'data-symfony--ux-cropperjs--cropper-options-value' => json_encode($options['cropper_options']),
                ],
            ])
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['class'] = trim(($view->vars['attr']['class'] ?? '').' cropperjs');

        // Remove data-controller attribute as it's already on the child input
        if (isset($view->vars['attr']['data-controller'])) {
            unset($view->vars['attr']['data-controller']);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('public_url');
        $resolver->setAllowedTypes('public_url', 'string');
        $resolver->setDefault('cropper_options', []);

        $resolver->setDefaults([
            'label' => false,
            'data_class' => Crop::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'cropper';
    }
}
