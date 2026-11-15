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
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
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
        $rendered = $this->renderForm(['attr' => ['data-controller' => 'mydropzone']]);

        $this->assertSame(
            '<form name="form" method="post" enctype="multipart/form-data"><div id="form"><div><label for="form_photo" class="required">Photo</label><div class="dropzone-container" data-controller="mydropzone symfony--ux-dropzone--dropzone" data-symfony--ux-dropzone--dropzone-remove-label-value="Remove">
        <input type="file" id="form_photo" name="form[photo]" required="required" class="dropzone-input" data-symfony--ux-dropzone--dropzone-target="input" />

        <div class="dropzone-placeholder" data-symfony--ux-dropzone--dropzone-target="placeholder"></div>

        <div class="dropzone-preview" data-symfony--ux-dropzone--dropzone-target="preview" hidden>
            <button class="dropzone-preview-button" type="button"
                    data-symfony--ux-dropzone--dropzone-target="previewClearButton"></button>

            <div class="dropzone-preview-image" hidden
                 data-symfony--ux-dropzone--dropzone-target="previewImage"></div>

            <div data-symfony--ux-dropzone--dropzone-target="previewFilename" class="dropzone-preview-filename"></div>
        </div>

    </div></div></div></form>
',
            str_replace(' >', '>', $rendered)
        );
    }

    public function testContainerAttr(): void
    {
        $view = Forms::createFormFactory()->create(DropzoneType::class, null, ['attr' => ['data-controller' => 'mydropzone']])->createView();

        $this->assertSame([
            'class' => 'dropzone-container',
            'data-controller' => 'mydropzone symfony--ux-dropzone--dropzone',
            'data-symfony--ux-dropzone--dropzone-remove-label-value' => 'Remove',
        ], $view->vars['container_attr']);
    }

    public function testContainerAttrMultiple(): void
    {
        $view = Forms::createFormFactory()->create(DropzoneType::class, null, ['multiple' => true, 'max_files' => 3])->createView();

        $this->assertSame([
            'class' => 'dropzone-container dropzone-container-multiple',
            'data-controller' => 'symfony--ux-dropzone--dropzone',
            'data-symfony--ux-dropzone--dropzone-remove-label-value' => 'Remove',
            'data-symfony--ux-dropzone--dropzone-multiple-value' => 'true',
            'data-symfony--ux-dropzone--dropzone-max-files-value' => '3',
        ], $view->vars['container_attr']);
    }

    public function testRenderRemoveLabelPassedToTheWidget(): void
    {
        $rendered = $this->renderWidget(['remove_label' => 'Remove', 'translation_domain' => 'dropzone_test']);

        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-remove-label-value="Delete this file"', $rendered);
    }

    public function testRenderContainerAttr(): void
    {
        $rendered = $this->render('dropzone_container_attr.html.twig', 'photo', ['multiple' => true, 'max_files' => 2]);

        $this->assertSame('<div class="dropzone-container dropzone-container-multiple" data-controller="symfony--ux-dropzone--dropzone" data-symfony--ux-dropzone--dropzone-remove-label-value="Remove" data-symfony--ux-dropzone--dropzone-multiple-value="true" data-symfony--ux-dropzone--dropzone-max-files-value="2"></div>', trim($rendered));
    }

    public function testRenderTranslatedRemoveLabel(): void
    {
        $rendered = $this->renderForm(['translation_domain' => 'dropzone_test']);

        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-remove-label-value="Delete this file"', $rendered);
    }

    public function testRenderDataControllerPassedToTheWidget(): void
    {
        $rendered = $this->renderWidget(['attr' => ['data-controller' => 'other']]);

        $this->assertStringContainsString('<div class="dropzone-container" data-controller="other symfony--ux-dropzone--dropzone"', $rendered);
        $this->assertStringNotContainsString('data-controller=""', $rendered);
    }

    public function testRenderFormMultiple(): void
    {
        $rendered = $this->renderForm(['multiple' => true], 'photos');

        $this->assertStringContainsString('dropzone-container dropzone-container-multiple', $rendered);
        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-multiple-value="true"', $rendered);
        $this->assertStringContainsString('name="form[photos][]"', $rendered);
        $this->assertStringContainsString('multiple="multiple"', $rendered);
        $this->assertStringContainsString('class="dropzone-preview-list" data-symfony--ux-dropzone--dropzone-target="previewList"', $rendered);
        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-remove-label-value="Remove"', $rendered);
        $this->assertStringNotContainsString('<template', $rendered);
        $this->assertStringNotContainsString('max-files-value', $rendered);
    }

    public function testRenderFormMultipleWithPreviewItemTemplate(): void
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        $form = $container->get(FormFactoryInterface::class)->createBuilder()
            ->add('photos', DropzoneType::class, ['multiple' => true])
            ->getForm()
        ;

        $rendered = $container->get(Environment::class)->render('dropzone_form_preview_item_template.html.twig', ['form' => $form->createView()]);

        $this->assertStringContainsString('<ul class="dropzone-preview-list" data-symfony--ux-dropzone--dropzone-target="previewList"></ul>
<template data-symfony--ux-dropzone--dropzone-target="previewItemTemplate"><li data-dropzone-slot="filename"></li></template>
    </div>', $rendered);
    }

    public function testRenderFormSingleWithPreviewItemTemplate(): void
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        $form = $container->get(FormFactoryInterface::class)->createBuilder()
            ->add('photo', DropzoneType::class)
            ->getForm()
        ;

        $rendered = $container->get(Environment::class)->render('dropzone_form_preview_item_template.html.twig', ['form' => $form->createView()]);

        $this->assertStringContainsString('<template data-symfony--ux-dropzone--dropzone-target="previewItemTemplate"><li data-dropzone-slot="filename"></li></template>', $rendered);
    }

    public function testRenderDefaultPlaceholder(): void
    {
        $rendered = $this->renderForm([]);

        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-target="placeholder">Drag and drop or browse</div>', $rendered);
        $this->assertStringNotContainsString('placeholder="', $rendered);
    }

    public function testRenderPlaceholder(): void
    {
        $rendered = $this->renderForm(['placeholder' => 'Choose a photo']);

        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-target="placeholder">Choose a photo</div>', $rendered);
        $this->assertStringNotContainsString('placeholder="', $rendered);
    }

    public function testRenderPlaceholderWithAttr(): void
    {
        $rendered = $this->renderForm(['placeholder' => 'Choose a photo', 'attr' => ['data-controller' => 'mydropzone']]);

        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-target="placeholder">Choose a photo</div>', $rendered);
    }

    public function testRenderTranslatablePlaceholder(): void
    {
        $rendered = $this->renderForm(['placeholder' => new class implements TranslatableInterface {
            public function trans(TranslatorInterface $translator, ?string $locale = null): string
            {
                return 'Translated placeholder';
            }
        }]);

        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-target="placeholder">Translated placeholder</div>', $rendered);
    }

    public function testRenderTranslatablePlaceholderWithoutTranslationDomain(): void
    {
        $rendered = $this->renderForm(['translation_domain' => false, 'placeholder' => new class implements TranslatableInterface {
            public function trans(TranslatorInterface $translator, ?string $locale = null): string
            {
                return 'Translated placeholder';
            }
        }]);

        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-target="placeholder">Translated placeholder</div>', $rendered);
    }

    public function testRenderPlaceholderPassedToTheWidget(): void
    {
        $rendered = $this->renderWidget(['attr' => ['placeholder' => 'Drop here']]);

        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-target="placeholder">Drop here</div>', $rendered);
        $this->assertStringNotContainsString('placeholder="', $rendered);
    }

    public function testRenderAttrPassedToTheWidgetKeepsThePlaceholder(): void
    {
        $rendered = $this->renderWidget(['attr' => ['class' => 'custom']]);

        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-target="placeholder">Drag and drop or browse</div>', $rendered);
    }

    public function testKeepPlaceholderInViewAttr(): void
    {
        $this->assertSame('Drag and drop or browse', Forms::createFormFactory()->create(DropzoneType::class)->createView()->vars['attr']['placeholder']);
        $this->assertArrayNotHasKey('placeholder', Forms::createFormFactory()->create(DropzoneType::class, null, ['placeholder' => false])->createView()->vars['attr']);
    }

    public function testPlaceholderViewVariable(): void
    {
        $factory = Forms::createFormFactory();
        $translatable = new class implements TranslatableInterface {
            public function trans(TranslatorInterface $translator, ?string $locale = null): string
            {
                return 'Translated placeholder';
            }
        };

        $this->assertSame('Drag and drop or browse', $factory->create(DropzoneType::class)->createView()->vars['placeholder']);
        $this->assertSame('Choose a photo', $factory->create(DropzoneType::class, null, ['placeholder' => 'Choose a photo'])->createView()->vars['placeholder']);
        $this->assertFalse($factory->create(DropzoneType::class, null, ['placeholder' => false])->createView()->vars['placeholder']);
        $this->assertSame($translatable, $factory->create(DropzoneType::class, null, ['placeholder' => $translatable])->createView()->vars['placeholder']);
    }

    public function testPlaceholderViewVariableFromAttr(): void
    {
        [$view] = $this->createViewCollectingDeprecations(['attr' => ['placeholder' => 'Choose a photo']]);

        $this->assertSame('Choose a photo', $view->vars['placeholder']);
    }

    public function testRenderPlaceholderVariablePassedToTheWidget(): void
    {
        $rendered = $this->renderWidget(['placeholder' => 'Drop here']);

        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-target="placeholder">Drop here</div>', $rendered);
        $this->assertStringNotContainsString('placeholder="', $rendered);
    }

    public function testRenderPlaceholderVariablePassedToTheWidgetWithoutPlaceholderOption(): void
    {
        $rendered = $this->renderWidget(['placeholder' => 'Drop here'], ['placeholder' => false]);

        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-target="placeholder">Drop here</div>', $rendered);
    }

    public function testRenderPlaceholderPassedToTheWidgetWithoutPlaceholderOption(): void
    {
        $rendered = $this->renderWidget(['attr' => ['placeholder' => 'Drop here']], ['placeholder' => false]);

        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-target="placeholder">Drop here</div>', $rendered);
        $this->assertStringNotContainsString('placeholder="', $rendered);
    }

    public function testRenderWithoutPlaceholder(): void
    {
        $rendered = $this->renderForm(['placeholder' => false]);

        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-target="placeholder"></div>', $rendered);
    }

    public function testPlaceholderCannotBeTrue(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->renderForm(['placeholder' => true]);
    }

    public function testAttrPlaceholderMustBeAString(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->createViewCollectingDeprecations(['attr' => ['placeholder' => ['Choose a photo']]]);
    }

    public function testAttrPlaceholderIsDeprecated(): void
    {
        [$view, $deprecations] = $this->createViewCollectingDeprecations(['attr' => ['placeholder' => 'Choose a photo']]);

        $this->assertSame(['Since symfony/ux-dropzone 3.7: Passing the placeholder in the "attr" option of "Symfony\UX\Dropzone\Form\DropzoneType" is deprecated, use the "placeholder" option instead.'], $deprecations);
        $this->assertSame('Choose a photo', $view->vars['attr']['placeholder']);
    }

    public function testAttrPlaceholderIsDeprecatedOncePerField(): void
    {
        [, $deprecations] = $this->createViewCollectingDeprecations(['attr' => ['placeholder' => 'Choose a photo']], 2);

        $this->assertCount(1, $deprecations);
    }

    public function testPlaceholderWinsOverAttrPlaceholder(): void
    {
        [$view, $deprecations] = $this->createViewCollectingDeprecations(['placeholder' => 'Choose a photo', 'attr' => ['placeholder' => 'Drop it']]);

        $this->assertSame(['Since symfony/ux-dropzone 3.7: Passing the placeholder in the "attr" option of "Symfony\UX\Dropzone\Form\DropzoneType" is deprecated, use the "placeholder" option instead.'], $deprecations);
        $this->assertSame('Choose a photo', $view->vars['attr']['placeholder']);
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

    private function renderForm(array $options, string $name = 'photo'): string
    {
        return $this->render('dropzone_form.html.twig', $name, $options);
    }

    private function renderWidget(array $variables, array $options = []): string
    {
        return $this->render('dropzone_widget.html.twig', 'photo', $options, ['variables' => $variables]);
    }

    private function render(string $template, string $name, array $options, array $context = []): string
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        $form = $container->get(FormFactoryInterface::class)->createBuilder()
            ->add($name, DropzoneType::class, $options)
            ->getForm()
        ;

        return $container->get(Environment::class)->render($template, ['form' => $form->createView()] + $context);
    }

    /**
     * @return array{FormView, list<string>}
     */
    private function createViewCollectingDeprecations(array $options, int $views = 1): array
    {
        $deprecations = [];
        set_error_handler(static function (int $type, string $message) use (&$deprecations): bool {
            if (str_starts_with($message, 'Since symfony/ux-dropzone ')) {
                $deprecations[] = $message;
            }

            return true;
        }, \E_USER_DEPRECATED);

        try {
            $form = Forms::createFormFactory()->create(DropzoneType::class, null, $options);
            for ($i = 0; $i < $views; ++$i) {
                $view = $form->createView();
            }
        } finally {
            restore_error_handler();
        }

        return [$view, $deprecations];
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
