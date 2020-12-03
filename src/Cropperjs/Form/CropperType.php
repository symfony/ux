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
                    'data-controller' => trim(($options['attr']['data-controller'] ?? '').' @symfony/ux-cropperjs/cropper'),
                    'data-public-url' => $options['public_url'],
                    'data-view-mode' => $options['view_mode'],
                    'data-drag-mode' => $options['drag_mode'],
                    'data-aspect-ratio' => $options['aspect_ratio'] ?: false,
                    'data-initial-aspect-ratio' => $options['initial_aspect_ratio'] ?: false,
                    'data-responsive' => $options['responsive'],
                    'data-restore' => $options['restore'],
                    'data-check-cross-origin' => $options['check_cross_origin'],
                    'data-check-orientation' => $options['check_orientation'],
                    'data-modal' => $options['modal'],
                    'data-guides' => $options['guides'],
                    'data-center' => $options['center'],
                    'data-highlight' => $options['highlight'],
                    'data-background' => $options['background'],
                    'data-auto-crop' => $options['auto_crop'],
                    'data-auto-crop-area' => $options['auto_crop_area'],
                    'data-movable' => $options['movable'],
                    'data-rotatable' => $options['rotatable'],
                    'data-scalable' => $options['scalable'],
                    'data-zoomable' => $options['zoomable'],
                    'data-zoom-on-touch' => $options['zoom_on_touch'],
                    'data-zoom-on-wheel' => $options['zoom_on_wheel'],
                    'data-wheel-zoom-ratio' => $options['wheel_zoom_ratio'],
                    'data-crop-box-movable' => $options['crop_box_movable'],
                    'data-crop-box-resizable' => $options['crop_box_resizable'],
                    'data-toggle-drag-mode-on-dblclick' => $options['toggle_drag_mode_on_dblclick'],
                    'data-min-container-width' => $options['min_container_width'],
                    'data-min-container-height' => $options['min_container_height'],
                    'data-min-canvas-width' => $options['min_canvas_width'],
                    'data-min-canvas-height' => $options['min_canvas_height'],
                    'data-min-crop-box-width' => $options['min_crop_box_width'],
                    'data-min-crop-box-height' => $options['min_crop_box_height'],
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

        $resolver->setDefined([
            'view_mode',
            'drag_mode',
            'aspect_ratio',
            'initial_aspect_ratio',
            'responsive',
            'restore',
            'check_cross_origin',
            'check_orientation',
            'modal',
            'guides',
            'center',
            'highlight',
            'background',
            'auto_crop',
            'auto_crop_area',
            'movable',
            'rotatable',
            'scalable',
            'zoomable',
            'zoom_on_touch',
            'zoom_on_wheel',
            'wheel_zoom_ratio',
            'crop_box_movable',
            'crop_box_resizable',
            'toggle_drag_mode_on_dblclick',
            'min_container_width',
            'min_container_height',
            'min_canvas_width',
            'min_canvas_height',
            'min_crop_box_width',
            'min_crop_box_height',
        ]);

        $resolver->setAllowedTypes('view_mode', ['int']);
        $resolver->setAllowedTypes('drag_mode', ['string']);
        $resolver->setAllowedTypes('aspect_ratio', ['double', 'null']);
        $resolver->setAllowedTypes('initial_aspect_ratio', ['double', 'null']);
        $resolver->setAllowedTypes('responsive', ['bool']);
        $resolver->setAllowedTypes('restore', ['bool']);
        $resolver->setAllowedTypes('check_cross_origin', ['bool']);
        $resolver->setAllowedTypes('check_orientation', ['bool']);
        $resolver->setAllowedTypes('modal', ['bool']);
        $resolver->setAllowedTypes('guides', ['bool']);
        $resolver->setAllowedTypes('center', ['bool']);
        $resolver->setAllowedTypes('highlight', ['bool']);
        $resolver->setAllowedTypes('background', ['bool']);
        $resolver->setAllowedTypes('auto_crop', ['bool']);
        $resolver->setAllowedTypes('auto_crop_area', ['float']);
        $resolver->setAllowedTypes('movable', ['bool']);
        $resolver->setAllowedTypes('rotatable', ['bool']);
        $resolver->setAllowedTypes('scalable', ['bool']);
        $resolver->setAllowedTypes('zoomable', ['bool']);
        $resolver->setAllowedTypes('zoom_on_touch', ['bool']);
        $resolver->setAllowedTypes('zoom_on_wheel', ['bool']);
        $resolver->setAllowedTypes('wheel_zoom_ratio', ['float']);
        $resolver->setAllowedTypes('crop_box_movable', ['bool']);
        $resolver->setAllowedTypes('crop_box_resizable', ['bool']);
        $resolver->setAllowedTypes('toggle_drag_mode_on_dblclick', ['bool']);
        $resolver->setAllowedTypes('min_container_width', ['int']);
        $resolver->setAllowedTypes('min_container_height', ['int']);
        $resolver->setAllowedTypes('min_canvas_width', ['int']);
        $resolver->setAllowedTypes('min_canvas_height', ['int']);
        $resolver->setAllowedTypes('min_crop_box_width', ['int']);
        $resolver->setAllowedTypes('min_crop_box_height', ['int']);

        $resolver->setDefaults([
            'label' => false,
            'data_class' => Crop::class,
            'view_mode' => 0,
            'drag_mode' => 'crop',
            'initial_aspect_ratio' => null,
            'aspect_ratio' => null,
            'responsive' => true,
            'restore' => true,
            'check_cross_origin' => true,
            'check_orientation' => true,
            'modal' => true,
            'guides' => true,
            'center' => true,
            'highlight' => true,
            'background' => true,
            'auto_crop' => true,
            'auto_crop_area' => 0.8,
            'movable' => false,
            'rotatable' => true,
            'scalable' => false,
            'zoomable' => false,
            'zoom_on_touch' => true,
            'zoom_on_wheel' => true,
            'wheel_zoom_ratio' => 0.1,
            'crop_box_movable' => true,
            'crop_box_resizable' => true,
            'toggle_drag_mode_on_dblclick' => true,
            'min_container_width' => 200,
            'min_container_height' => 100,
            'min_canvas_width' => 0,
            'min_canvas_height' => 0,
            'min_crop_box_width' => 0,
            'min_crop_box_height' => 0,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'cropper';
    }
}
