<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Dropzone\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\UX\Dropzone\Form\DropzoneType;
use Symfony\UX\Dropzone\Tests\Kernel\TwigAppKernel;
use Twig\Environment;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class DropzoneTypeTest extends TestCase
{
    public function testRenderForm()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        $form = $container->get(FormFactoryInterface::class)->createBuilder()
            ->add('photo', DropzoneType::class, ['attr' => ['data-controller' => 'mydropzone']])
            ->getForm()
        ;

        $rendered = $container->get(Environment::class)->render('dropzone_form.html.twig', ['form' => $form->createView()]);

        $this->assertSame(
            '<form name="form" method="post" enctype="multipart/form-data"><div id="form"><div><label for="form_photo" class="required">Photo</label><div class="dropzone-container" data-controller="mydropzone symfony--ux-dropzone--dropzone">
        <input type="file" id="form_photo" name="form[photo]" required="required" data-controller="" class="dropzone-input" data-symfony--ux-dropzone--dropzone-target="input" />

        <div class="dropzone-placeholder" data-symfony--ux-dropzone--dropzone-target="placeholder"></div>

        <div class="dropzone-preview" data-symfony--ux-dropzone--dropzone-target="preview" style="display: none">
            <button class="dropzone-preview-button" type="button"
                    data-symfony--ux-dropzone--dropzone-target="previewClearButton"></button>

            <div class="dropzone-preview-image" style="display: none"
                 data-symfony--ux-dropzone--dropzone-target="previewImage"></div>

            <div data-symfony--ux-dropzone--dropzone-target="previewFilename" class="dropzone-preview-filename"></div>
        </div>
    </div></div></div></form>
',
            str_replace(' >', '>', $rendered)
        );
    }
}
