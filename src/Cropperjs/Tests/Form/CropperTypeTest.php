<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Symfony\UX\Cropperjs;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Cropperjs\Form\CropperType;
use Tests\Symfony\UX\Cropperjs\Kernel\TwigAppKernel;

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

        $form = $container->get('form.factory')->createBuilder()
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

        $rendered = $container->get('twig')->render('cropper_form.html.twig', ['form' => $form->createView()]);

        $this->assertSame(
            '<form name="form" method="post">'.
                '<div id="form">'.
                    '<div>'.
                        '<div id="form_photo" class="cropperjs">'.
                            '<input type="hidden" id="form_photo_options" name="form[photo][options]" '.
                                'data-controller="mycropper @symfony/ux-cropperjs/cropper" '.
                                'data-public-url="/public/url.jpg" '.
                                'data-view-mode="1" '.
                                'data-drag-mode="move" '.
                                'data-aspect-ratio="'.(2000 / 1800).'" '.
                                'data-initial-aspect-ratio="'.(2000 / 1800).'" '.
                                'data-responsive="data-responsive" '.
                                'data-restore="data-restore" '.
                                'data-check-cross-origin="data-check-cross-origin" '.
                                'data-check-orientation="data-check-orientation" '.
                                'data-modal="data-modal" '.
                                'data-guides="data-guides" '.
                                'data-center="data-center" '.
                                'data-highlight="data-highlight" '.
                                'data-background="data-background" '.
                                'data-auto-crop="data-auto-crop" '.
                                'data-auto-crop-area="0.1" '.
                                'data-movable="data-movable" '.
                                'data-rotatable="data-rotatable" '.
                                'data-scalable="data-scalable" '.
                                'data-zoomable="data-zoomable" '.
                                'data-zoom-on-touch="data-zoom-on-touch" '.
                                'data-zoom-on-wheel="data-zoom-on-wheel" '.
                                'data-wheel-zoom-ratio="0.2" '.
                                'data-crop-box-movable="data-crop-box-movable" '.
                                'data-crop-box-resizable="data-crop-box-resizable" '.
                                'data-toggle-drag-mode-on-dblclick="data-toggle-drag-mode-on-dblclick" '.
                                'data-min-container-width="1" '.
                                'data-min-container-height="2" '.
                                'data-min-canvas-width="3" '.
                                'data-min-canvas-height="4" '.
                                'data-min-crop-box-width="5" '.
                                'data-min-crop-box-height="6" />'.
                        '</div>'.
                    '</div>'.
                '</div>'.
            '</form>
',
            $rendered
        );
    }

    public function testRenderNoOptions()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        $form = $container->get('form.factory')->createBuilder()
            ->add('photo', CropperType::class, [
                'public_url' => '/public/url.jpg',
                'attr' => ['data-controller' => 'mycropper'],
            ])
            ->getForm()
        ;

        $rendered = $container->get('twig')->render('cropper_form.html.twig', ['form' => $form->createView()]);

        $this->assertSame(
            '<form name="form" method="post">'.
                '<div id="form">'.
                    '<div>'.
                        '<div id="form_photo" class="cropperjs">'.
                            '<input type="hidden" id="form_photo_options" name="form[photo][options]" '.
                                'data-controller="mycropper @symfony/ux-cropperjs/cropper" '.
                                'data-public-url="/public/url.jpg" '.
                                'data-view-mode="0" '.
                                'data-drag-mode="crop"   '.
                                'data-responsive="data-responsive" '.
                                'data-restore="data-restore" '.
                                'data-check-cross-origin="data-check-cross-origin" '.
                                'data-check-orientation="data-check-orientation" '.
                                'data-modal="data-modal" '.
                                'data-guides="data-guides" '.
                                'data-center="data-center" '.
                                'data-highlight="data-highlight" '.
                                'data-background="data-background" '.
                                'data-auto-crop="data-auto-crop" '.
                                'data-auto-crop-area="0.8"  '.
                                'data-rotatable="data-rotatable"   '.
                                'data-zoom-on-touch="data-zoom-on-touch" '.
                                'data-zoom-on-wheel="data-zoom-on-wheel" '.
                                'data-wheel-zoom-ratio="0.1" '.
                                'data-crop-box-movable="data-crop-box-movable" '.
                                'data-crop-box-resizable="data-crop-box-resizable" '.
                                'data-toggle-drag-mode-on-dblclick="data-toggle-drag-mode-on-dblclick" '.
                                'data-min-container-width="200" '.
                                'data-min-container-height="100" '.
                                'data-min-canvas-width="0" '.
                                'data-min-canvas-height="0" '.
                                'data-min-crop-box-width="0" '.
                                'data-min-crop-box-height="0" />'.
                        '</div>'.
                    '</div>'.
                '</div>'.
            '</form>
',
            $rendered
        );
    }
}
