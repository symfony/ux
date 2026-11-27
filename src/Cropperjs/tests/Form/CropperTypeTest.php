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

use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\UX\Cropperjs\Form\CropperType;
use Symfony\UX\Cropperjs\Intervention\InterventionImage;
use Symfony\UX\Cropperjs\Model\Crop;
use Symfony\UX\Cropperjs\Tests\Kernel\TwigAppKernel;
use Twig\Environment;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class CropperTypeTest extends TestCase
{
    public function testRenderFull(): void
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

        $this->assertStringContainsString('<div id="form_photo" class="cropperjs">', $rendered);
        $this->assertStringContainsString(
            '<input type="hidden" id="form_photo_options" name="form[photo][options]" '.
                'data-controller="mycropper symfony--ux-cropperjs--cropper" '.
                'data-symfony--ux-cropperjs--cropper-public-url-value="/public/url.jpg" '.
                'data-symfony--ux-cropperjs--cropper-options-value="{&quot;viewMode&quot;:1,&quot;dragMode&quot;:&quot;move&quot;}"',
            $rendered,
        );
    }

    public function testRenderNoOptions(): void
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

        $this->assertStringContainsString(
            '<input type="hidden" id="form_photo_options" name="form[photo][options]" '.
                'data-controller="mycropper symfony--ux-cropperjs--cropper" '.
                'data-symfony--ux-cropperjs--cropper-public-url-value="/public/url.jpg" '.
                'data-symfony--ux-cropperjs--cropper-options-value="[]"',
            str_replace(' >', '>', $rendered)
        );
    }

    public function testSubmitValidOptions(): void
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');
        $crop = new Crop(self::createImageManager(), '/path/to/image.jpg');
        $form = $container->get(FormFactoryInterface::class)->createBuilder()
            ->add('photo', CropperType::class, ['public_url' => '/public/url.jpg', 'data' => $crop])
            ->getForm()
        ;

        $form->submit(['photo' => ['options' => '{"x":1,"y":2,"width":3,"height":4,"rotate":0}']]);

        $this->assertTrue($form->isValid());
        $this->assertSame(['x' => 1, 'y' => 2, 'width' => 3, 'height' => 4, 'rotate' => 0], json_decode($crop->getOptions(), true));
    }

    #[TestWith(['"abc"'])]
    #[TestWith(['{not json'])]
    #[TestWith(['{"width":[1],"height":2}'])]
    public function testSubmitInvalidOptionsIsAFormError(string $options): void
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');
        $form = $container->get(FormFactoryInterface::class)->createBuilder()
            ->add('photo', CropperType::class, ['public_url' => '/public/url.jpg', 'data' => new Crop(self::createImageManager(), '/path/to/image.jpg')])
            ->getForm()
        ;

        $form->submit(['photo' => ['options' => $options]]);

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
    }

    private static function createImageManager(): ImageManager
    {
        $driver = InterventionImage::supportsDriverClasses() ? GdDriver::class : 'gd';

        return match (InterventionImage::major()) {
            InterventionImage::V4 => ImageManager::usingDriver($driver),
            InterventionImage::V3 => ImageManager::withDriver($driver),
            default => new ImageManager(['driver' => $driver]),
        };
    }
}
