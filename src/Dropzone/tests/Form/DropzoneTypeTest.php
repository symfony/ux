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
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
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
    public function testRenderForm(): void
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

    public function testRenderFormMultiple(): void
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        $form = $container->get(FormFactoryInterface::class)->createBuilder()
            ->add('photos', DropzoneType::class, ['multiple' => true])
            ->getForm()
        ;

        $rendered = $container->get(Environment::class)->render('dropzone_form.html.twig', ['form' => $form->createView()]);

        $this->assertStringContainsString('dropzone-container dropzone-container-multiple', $rendered);
        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-multiple-value="true"', $rendered);
        $this->assertStringContainsString('name="form[photos][]"', $rendered);
        $this->assertStringContainsString('multiple="multiple"', $rendered);
        $this->assertStringContainsString('class="dropzone-preview-list" data-symfony--ux-dropzone--dropzone-target="previewList"', $rendered);
        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-remove-label-value="Remove"', $rendered);
        $this->assertStringNotContainsString('max-files-value', $rendered);
    }

    public function testRenderFormMultipleWithMaxFiles(): void
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        $form = $container->get(FormFactoryInterface::class)->createBuilder()
            ->add('photos', DropzoneType::class, ['multiple' => true, 'max_files' => 5])
            ->getForm()
        ;

        $rendered = $container->get(Environment::class)->render('dropzone_form.html.twig', ['form' => $form->createView()]);

        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-max-files-value="5"', $rendered);
    }

    public function testMaxFilesRequiresMultiple(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The "max_files" option requires the "multiple" option.');

        Forms::createFormFactory()->create(DropzoneType::class, null, ['max_files' => 5]);
    }

    public function testMaxFilesAddsCountConstraint(): void
    {
        $notBlank = new NotBlank();
        $form = $this->createValidatedForm(['multiple' => true, 'max_files' => 2, 'constraints' => [$notBlank]]);

        $this->assertEquals([$notBlank, new Count(max: 2)], $form->getConfig()->getOption('constraints'));
    }

    public function testMaxFilesRejectsTooManyFiles(): void
    {
        $form = $this->createValidatedForm(['multiple' => true, 'max_files' => 2]);

        $form->submit([
            new UploadedFile(__FILE__, 'a.php', null, null, true),
            new UploadedFile(__FILE__, 'b.php', null, null, true),
            new UploadedFile(__FILE__, 'c.php', null, null, true),
        ]);

        $this->assertFalse($form->isValid());
        $this->assertInstanceOf(Count::class, $form->getErrors()[0]->getCause()->getConstraint());
    }

    public function testNoCountConstraintWithoutMaxFiles(): void
    {
        $form = $this->createValidatedForm(['multiple' => true]);

        $this->assertSame([], $form->getConfig()->getOption('constraints'));
    }

    public function testMaxFilesWithoutValidator(): void
    {
        $form = Forms::createFormFactory()->create(DropzoneType::class, null, ['multiple' => true, 'max_files' => 2]);

        $this->assertFalse($form->getConfig()->hasOption('constraints'));
    }

    public function testMaxFilesMustBePositive(): void
    {
        $this->expectException(InvalidOptionsException::class);

        Forms::createFormFactory()->create(DropzoneType::class, null, ['multiple' => true, 'max_files' => 0]);
    }

    private function createValidatedForm(array $options): FormInterface
    {
        return Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory()
            ->create(DropzoneType::class, null, $options)
        ;
    }
}
