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
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
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
        $rendered = $this->renderForm(['multiple' => true], 'photos');

        $this->assertStringContainsString('dropzone-container dropzone-container-multiple', $rendered);
        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-multiple-value="true"', $rendered);
        $this->assertStringContainsString('name="form[photos][]"', $rendered);
        $this->assertStringContainsString('multiple="multiple"', $rendered);
        $this->assertStringContainsString('class="dropzone-preview-list" data-symfony--ux-dropzone--dropzone-target="previewList"', $rendered);
        $this->assertStringContainsString('data-symfony--ux-dropzone--dropzone-remove-label-value="Remove"', $rendered);
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

    private function renderForm(array $options, string $name = 'photo'): string
    {
        return $this->render('dropzone_form.html.twig', $name, $options);
    }

    private function renderWidget(array $variables): string
    {
        return $this->render('dropzone_widget.html.twig', 'photo', [], ['variables' => $variables]);
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
}
