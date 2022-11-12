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
                'cropper_options' => [
                    'viewMode' => 1,
                    'dragMode' => 'move',
                ],
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
                                'data-symfony--ux-cropperjs--cropper-options-value="{&quot;viewMode&quot;:1,&quot;dragMode&quot;:&quot;move&quot;}" />'.
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
                                'data-symfony--ux-cropperjs--cropper-options-value="[]" />'.
                        '</div>'.
                    '</div>'.
                '</div>'.
            '</form>
',
            str_replace(' >', '>', $rendered)
        );
    }
}
