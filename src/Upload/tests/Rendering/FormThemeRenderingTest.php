<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Rendering;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\UX\Upload\Form\FileUploadType;
use Twig\Environment;

/**
 * Markup produced by the package form theme.
 *
 * These assertions only need the rendered HTML, so they run against Twig
 * directly instead of a browser. Behaviour that depends on the Stimulus
 * controller (upload round-trips, action visibility, computed styles) lives in
 * the Playwright specs under assets/test/browser instead.
 */
final class FormThemeRenderingTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return RenderingKernel::class;
    }

    public function testDropzoneAndFileInputRender()
    {
        $html = $this->render(layout: 'dropzone', preview: true);

        self::assertCount(1, $html->filter('.ux-upload'));
        self::assertCount(1, $html->filter('.ux-upload__dropzone'));
        self::assertCount(1, $html->filter('.ux-upload__input'));
        self::assertCount(1, $html->filter('.ux-upload__list > .ux-upload__dropzone'));
        self::assertSame('dropzone', $html->filter('.ux-upload')->attr('data-ux-upload-layout'));
        self::assertStringContainsString('ux-upload--previews', (string) $html->filter('.ux-upload')->attr('class'));
        self::assertCount(1, $html->filter('[data-test-upload-global-theme]'));
    }

    public function testDropzoneRendersASeparatePickerInstruction()
    {
        $html = $this->render(layout: 'dropzone');

        $instruction = $html->filter('.ux-upload__instruction');
        self::assertSame('Drop files here or click to browse', trim($instruction->text()));
        self::assertSame('span', $instruction->nodeName());
        self::assertSame(
            'File upload area. Drop files or press to browse.',
            $html->filter('.ux-upload__input')->attr('aria-label'),
        );
    }

    public function testApplicationFormThemeCanOverrideOneBlockAndRenderItsParent()
    {
        $html = $this->render(layout: 'dropzone', theme: 'upload_application_theme.html.twig');

        $override = $html->filter('[data-test-upload-dropzone-block-override]');
        self::assertCount(1, $override);
        self::assertCount(1, $override->filter('.ux-upload__dropzone'));
        self::assertCount(1, $override->filter('.ux-upload__input'));
    }

    public function testRootAttributesUseSymfonyFormEscapingAndBooleanSemantics()
    {
        $html = $this->render(attributes: true);

        $upload = $html->filter('.ux-upload');
        self::assertStringContainsString('document-upload', (string) $upload->attr('class'));
        self::assertStringContainsString('symfony--ux-upload--upload', (string) $upload->attr('data-controller'));
        self::assertStringContainsString('test-widget', (string) $upload->attr('data-controller'));
        self::assertSame('"><script data-test-injected-script>alert(1)</script>', $upload->attr('data-label'));
        self::assertSame('data-ready', $upload->attr('data-ready'));
        self::assertNull($upload->attr('data-omitted'));

        $input = $html->filter('.ux-upload__input');
        self::assertStringContainsString('document-file-input', (string) $input->attr('class'));
        self::assertSame('environment', $input->attr('capture'));
        self::assertSame('native-file-input', $input->attr('data-input-label'));
        self::assertNotSame('application-controlled-id', $input->attr('id'));
        self::assertSame('file', $input->attr('type'));

        // The widget owns these: an application attribute must not take them over.
        self::assertNull($input->attr('name'));
        self::assertNull($input->attr('value'));
        self::assertNull($input->attr('multiple'));
        self::assertNull($input->attr('required'));
        self::assertNull($input->attr('disabled'));

        // The injected markup must survive as text, never as an element.
        self::assertCount(0, $html->filter('script[data-test-injected-script]'));
    }

    public function testFormRowRendersLabelHelpAndTransformationErrorsOnTheNativeInput()
    {
        $html = $this->render(layout: 'dropzone', row: true, invalid: true, attributes: true);

        $row = $html->filter('[data-test-row]');
        $input = $row->filter('.ux-upload__input');
        $inputId = $input->attr('id');
        self::assertNotNull($inputId);

        self::assertSame('Attachments', trim($row->filter('label[data-test-label]')->text()));
        self::assertSame($inputId, $row->filter('label[data-test-label]')->attr('for'));
        self::assertSame('Upload one or more documents.', trim($row->filter('[data-test-help]')->text()));
        self::assertSame(
            'The uploaded file reference is invalid or has expired.',
            trim($row->filter('ul li')->text()),
        );
        self::assertSame('true', $input->attr('aria-invalid'));

        $describedBy = (string) $input->attr('aria-describedby');
        self::assertStringContainsString($inputId.'_help', $describedBy);
        self::assertStringContainsString($inputId.'_error1', $describedBy);
        self::assertCount(1, $row->filter('#'.$inputId.'_help'));
        self::assertCount(1, $row->filter('#'.$inputId.'_error1'));

        // Only the hidden result input is submitted; the file input carries no name.
        self::assertCount(1, $row->filter('input[name]'));
        self::assertSame('document[attachments]', $row->filter('input[type="hidden"]')->attr('name'));
        self::assertNull($input->attr('name'));
    }

    public function testFormRowDelegatesToTheActiveApplicationFormTheme()
    {
        $bootstrap = $this->render(row: true, invalid: true, theme: 'bootstrap_5_layout.html.twig');
        self::assertSame('Attachments', trim($bootstrap->filter('.mb-3 > label.form-label')->text()));
        self::assertSame(
            'The uploaded file reference is invalid or has expired.',
            trim($bootstrap->filter('.mb-3 .invalid-feedback')->text()),
        );

        $tailwind = $this->render(row: true, invalid: true, theme: 'tailwind_2_layout.html.twig');
        self::assertSame('Attachments', trim($tailwind->filter('.mb-6 > label.block')->text()));
        self::assertSame(
            'The uploaded file reference is invalid or has expired.',
            trim($tailwind->filter('.mb-6 .text-red-700')->text()),
        );
    }

    public function testOptionalPresentationBlocksCanBeEmptied()
    {
        $html = $this->render(theme: 'upload_minimal_theme.html.twig');

        self::assertCount(1, $html->filter('.ux-upload'));
        self::assertCount(0, $html->filter('.ux-upload__visual'));
        self::assertCount(0, $html->filter('.ux-upload__progress'));
        self::assertCount(0, $html->filter('.ux-upload__actions'));
        self::assertCount(0, $html->filter('.ux-upload__summary'));
        self::assertCount(0, $html->filter('.ux-upload__errors'));
    }

    public function testDropzoneHasAccessibleAttributes()
    {
        $html = $this->render(layout: 'dropzone');

        self::assertNull($html->filter('.ux-upload__dropzone')->attr('role'));
        self::assertSame(
            'File upload area. Drop files or press to browse.',
            $html->filter('.ux-upload__input')->attr('aria-label'),
        );
    }

    public function testPickerMergesAllDropzoneActions()
    {
        $expectedActions = [
            'dragover->symfony--ux-upload--upload#dragover',
            'dragleave->symfony--ux-upload--upload#dragleave',
            'drop->symfony--ux-upload--upload#drop',
            'paste->symfony--ux-upload--upload#paste',
        ];

        $actions = $this->render()
            ->filter('[data-symfony--ux-upload--upload-target="dropzone"]')
            ->attr('data-action');

        self::assertNotNull($actions);
        foreach ($expectedActions as $expectedAction) {
            self::assertStringContainsString($expectedAction, $actions);
        }
        self::assertSame(4, substr_count($actions, '->'));
    }

    public function testManualUploadButtonIsRenderedInsideControllerScope()
    {
        $button = $this->render(manual: true)->filter('.ux-upload > .ux-upload__start');

        self::assertCount(1, $button);
        self::assertSame('symfony--ux-upload--upload#startAll', $button->attr('data-action'));
    }

    /**
     * Renders the upload field the way an application would, and returns its markup.
     */
    private function render(
        bool $row = false,
        string $layout = 'compact',
        ?string $theme = null,
        bool $attributes = false,
        bool $invalid = false,
        bool $multiple = false,
        bool $manual = false,
        bool $preview = false,
    ): Crawler {
        self::bootKernel();
        $factory = self::getContainer()->get('test.form.factory');
        \assert($factory instanceof FormFactoryInterface);

        $inputAttributes = $attributes ? [
            'class' => 'document-file-input',
            'capture' => 'environment',
            'data-input-label' => 'native-file-input',
            'id' => 'application-controlled-id',
            'name' => 'application-controlled-name',
            'type' => 'text',
            'value' => 'application-controlled-value',
            'multiple' => true,
            'required' => true,
            'disabled' => true,
        ] : [];
        $widgetAttributes = $attributes ? [
            'class' => 'document-upload',
            'data-controller' => 'test-widget',
            'data-label' => '"><script data-test-injected-script>alert(1)</script>',
            'data-ready' => true,
            'data-omitted' => false,
        ] : [];

        $options = [
            'attr' => $inputAttributes,
            'widget_attr' => $widgetAttributes,
            'label' => $row ? 'Attachments' : null,
            'label_attr' => $row ? ['data-test-label' => true] : [],
            'help' => $row ? 'Upload one or more documents.' : null,
            'help_attr' => $row ? ['data-test-help' => true] : [],
            'row_attr' => $row ? ['data-test-row' => true] : [],
            'multiple' => $multiple,
            'max_files' => $multiple ? 5 : 1,
            'auto_upload' => !$manual,
            'layout' => $layout,
            'show_preview' => $preview,
        ];

        if ($row) {
            $form = $factory->createNamedBuilder('document', FormType::class)
                ->add('attachments', FileUploadType::class, $options)
                ->getForm();
            if ($invalid) {
                $form->submit(['attachments' => '{invalid']);
            }
            $view = $form->createView()['attachments'];
        } else {
            $form = $factory->create(FileUploadType::class, null, $options);
            if ($invalid) {
                $form->submit('{invalid');
            }
            $view = $form->createView();
        }

        $twig = self::getContainer()->get('test.twig');
        \assert($twig instanceof Environment);

        $render = $row ? 'form_row' : 'form_widget';
        $source = null === $theme
            ? \sprintf('{{ %s(upload_field) }}', $render)
            : \sprintf('{%% form_theme upload_field %s %%}{{ %s(upload_field) }}', var_export($theme, true), $render);

        return new Crawler($twig->createTemplate($source)->render(['upload_field' => $view]));
    }
}
