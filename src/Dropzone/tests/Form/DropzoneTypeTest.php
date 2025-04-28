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
    <button class="dropzone-preview-button" type="button" data-symfony--ux-dropzone--dropzone-target="previewClearButton">
        <svg xmlns="http://www.w3.org/2000/svg" width="1.5em" height="1.5em" viewbox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15 9l-6 6m0-6l6 6m6-3a9 9 0 1 1-18 0a9 9 0 0 1 18 0"/></svg>
    </button>
    <div class="dropzone-preview" data-symfony--ux-dropzone--dropzone-target="preview" style="display: none"></div>
</div></div></div></form>
',
            str_replace(' >', '>', $rendered)
        );
    }
}
