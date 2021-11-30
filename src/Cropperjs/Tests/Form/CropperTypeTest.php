<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Cropperjs\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\UX\Cropperjs\Form\CropperType;
use Symfony\UX\Cropperjs\Tests\Kernel\TwigAppKernel;
use Twig\Environment;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class CropperTypeTest extends TestCase
{
    public function testRenderFull()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        $form = $container->get(FormFactoryInterface::class)->createBuilder()
            ->add('photo', CropperType::class, [
                'public_url' => '/public/url.jpg',
                'attr' => ['data-controller' => 'mycropper'],
                'view_mode' => 1,
                'drag_mode' => 'move',
                'initial_aspect_ratio' => 2000 / 1800,
                'aspect_ratio' => 2000 / 1800,
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
                'auto_crop_area' => 0.1,
                'movable' => true,
                'rotatable' => true,
                'scalable' => true,
                'zoomable' => true,
                'zoom_on_touch' => true,
                'zoom_on_wheel' => true,
                'wheel_zoom_ratio' => 0.2,
                'crop_box_movable' => true,
                'crop_box_resizable' => true,
                'toggle_drag_mode_on_dblclick' => true,
                'min_container_width' => 1,
                'min_container_height' => 2,
                'min_canvas_width' => 3,
                'min_canvas_height' => 4,
                'min_crop_box_width' => 5,
                'min_crop_box_height' => 6,
            ])
            ->getForm()
        ;

        $rendered = $container->get(Environment::class)->render('cropper_form.html.twig', ['form' => $form->createView()]);

        $this->assertSame(
            '<form name="form" method="post">'.
                '<div id="form">'.
                    '<div>'.
                        '<div id="form_photo" class="cropperjs">'.
                            '<input type="hidden" id="form_photo_options" name="form[photo][options]" '.
                                'data-controller="mycropper symfony--ux-cropperjs--cropper" '.
                                'data-symfony--ux-cropperjs--cropper-public-url-value="/public/url.jpg" '.
                                'data-symfony--ux-cropperjs--cropper-view-mode-value="1" '.
                                'data-symfony--ux-cropperjs--cropper-drag-mode-value="move" '.
                                'data-symfony--ux-cropperjs--cropper-aspect-ratio-value="'.(2000 / 1800).'" '.
                                'data-symfony--ux-cropperjs--cropper-initial-aspect-ratio-value="'.(2000 / 1800).'" '.
                                'data-symfony--ux-cropperjs--cropper-responsive-value="data-responsive" '.
                                'data-symfony--ux-cropperjs--cropper-restore-value="data-restore" '.
                                'data-symfony--ux-cropperjs--cropper-check-cross-origin-value="data-check-cross-origin" '.
                                'data-symfony--ux-cropperjs--cropper-check-orientation-value="data-check-orientation" '.
                                'data-symfony--ux-cropperjs--cropper-modal-value="data-modal" '.
                                'data-symfony--ux-cropperjs--cropper-guides-value="data-guides" '.
                                'data-symfony--ux-cropperjs--cropper-center-value="data-center" '.
                                'data-symfony--ux-cropperjs--cropper-highlight-value="data-highlight" '.
                                'data-symfony--ux-cropperjs--cropper-background-value="data-background" '.
                                'data-symfony--ux-cropperjs--cropper-auto-crop-value="data-auto-crop" '.
                                'data-symfony--ux-cropperjs--cropper-auto-crop-area-value="0.1" '.
                                'data-symfony--ux-cropperjs--cropper-movable-value="data-movable" '.
                                'data-symfony--ux-cropperjs--cropper-rotatable-value="data-rotatable" '.
                                'data-symfony--ux-cropperjs--cropper-scalable-value="data-scalable" '.
                                'data-symfony--ux-cropperjs--cropper-zoomable-value="data-zoomable" '.
                                'data-symfony--ux-cropperjs--cropper-zoom-on-touch-value="data-zoom-on-touch" '.
                                'data-symfony--ux-cropperjs--cropper-zoom-on-wheel-value="data-zoom-on-wheel" '.
                                'data-symfony--ux-cropperjs--cropper-wheel-zoom-ratio-value="0.2" '.
                                'data-symfony--ux-cropperjs--cropper-crop-box-movable-value="data-crop-box-movable" '.
                                'data-symfony--ux-cropperjs--cropper-crop-box-resizable-value="data-crop-box-resizable" '.
                                'data-symfony--ux-cropperjs--cropper-toggle-drag-mode-on-dblclick-value="data-toggle-drag-mode-on-dblclick" '.
                                'data-symfony--ux-cropperjs--cropper-min-container-width-value="1" '.
                                'data-symfony--ux-cropperjs--cropper-min-container-height-value="2" '.
                                'data-symfony--ux-cropperjs--cropper-min-canvas-width-value="3" '.
                                'data-symfony--ux-cropperjs--cropper-min-canvas-height-value="4" '.
                                'data-symfony--ux-cropperjs--cropper-min-crop-box-width-value="5" '.
                                'data-symfony--ux-cropperjs--cropper-min-crop-box-height-value="6" />'.
                        '</div>'.
                    '</div>'.
                '</div>'.
            '</form>
',
            str_replace(' >', '>', $rendered)
        );
    }

    public function testRenderNoOptions()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        $form = $container->get(FormFactoryInterface::class)->createBuilder()
            ->add('photo', CropperType::class, [
                'public_url' => '/public/url.jpg',
                'attr' => ['data-controller' => 'mycropper'],
            ])
            ->getForm()
        ;

        $rendered = $container->get(Environment::class)->render('cropper_form.html.twig', ['form' => $form->createView()]);

        $this->assertSame(
            '<form name="form" method="post">'.
                '<div id="form">'.
                    '<div>'.
                        '<div id="form_photo" class="cropperjs">'.
                            '<input type="hidden" id="form_photo_options" name="form[photo][options]" '.
                                'data-controller="mycropper symfony--ux-cropperjs--cropper" '.
                                'data-symfony--ux-cropperjs--cropper-public-url-value="/public/url.jpg" '.
                                'data-symfony--ux-cropperjs--cropper-view-mode-value="0" '.
                                'data-symfony--ux-cropperjs--cropper-drag-mode-value="crop"   '.
                                'data-symfony--ux-cropperjs--cropper-responsive-value="data-responsive" '.
                                'data-symfony--ux-cropperjs--cropper-restore-value="data-restore" '.
                                'data-symfony--ux-cropperjs--cropper-check-cross-origin-value="data-check-cross-origin" '.
                                'data-symfony--ux-cropperjs--cropper-check-orientation-value="data-check-orientation" '.
                                'data-symfony--ux-cropperjs--cropper-modal-value="data-modal" '.
                                'data-symfony--ux-cropperjs--cropper-guides-value="data-guides" '.
                                'data-symfony--ux-cropperjs--cropper-center-value="data-center" '.
                                'data-symfony--ux-cropperjs--cropper-highlight-value="data-highlight" '.
                                'data-symfony--ux-cropperjs--cropper-background-value="data-background" '.
                                'data-symfony--ux-cropperjs--cropper-auto-crop-value="data-auto-crop" '.
                                'data-symfony--ux-cropperjs--cropper-auto-crop-area-value="0.8"  '.
                                'data-symfony--ux-cropperjs--cropper-rotatable-value="data-rotatable"   '.
                                'data-symfony--ux-cropperjs--cropper-zoom-on-touch-value="data-zoom-on-touch" '.
                                'data-symfony--ux-cropperjs--cropper-zoom-on-wheel-value="data-zoom-on-wheel" '.
                                'data-symfony--ux-cropperjs--cropper-wheel-zoom-ratio-value="0.1" '.
                                'data-symfony--ux-cropperjs--cropper-crop-box-movable-value="data-crop-box-movable" '.
                                'data-symfony--ux-cropperjs--cropper-crop-box-resizable-value="data-crop-box-resizable" '.
                                'data-symfony--ux-cropperjs--cropper-toggle-drag-mode-on-dblclick-value="data-toggle-drag-mode-on-dblclick" '.
                                'data-symfony--ux-cropperjs--cropper-min-container-width-value="200" '.
                                'data-symfony--ux-cropperjs--cropper-min-container-height-value="100" '.
                                'data-symfony--ux-cropperjs--cropper-min-canvas-width-value="0" '.
                                'data-symfony--ux-cropperjs--cropper-min-canvas-height-value="0" '.
                                'data-symfony--ux-cropperjs--cropper-min-crop-box-width-value="0" '.
                                'data-symfony--ux-cropperjs--cropper-min-crop-box-height-value="0" />'.
                        '</div>'.
                    '</div>'.
                '</div>'.
            '</form>
',
            str_replace(' >', '>', $rendered)
        );
    }
}
