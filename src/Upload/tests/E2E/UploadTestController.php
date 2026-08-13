<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\E2E;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Upload\Form\FileUploadType;
use Twig\Environment;

/**
 * Renders the upload form page the browser test drives.
 *
 * A `multiple=true` query flag mirrors the multi-file scenario from the old
 * JS spec so a single route can serve both cases.
 */
final readonly class UploadTestController
{
    public function __construct(
        private Environment $twig,
        private FormFactoryInterface $formFactory,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $multiple = $request->query->getBoolean('multiple');
        $layout = 'dropzone' === $request->query->getString('layout') ? 'dropzone' : 'compact';
        $theme = match ($request->query->getString('theme')) {
            'application' => 'upload_application_theme.html.twig',
            'minimal' => 'upload_minimal_theme.html.twig',
            'bootstrap' => 'bootstrap_5_layout.html.twig',
            'tailwind' => 'tailwind_2_layout.html.twig',
            default => null,
        };
        $inputAttributes = $request->query->getBoolean('attributes') ? [
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
        $widgetAttributes = $request->query->getBoolean('attributes') ? [
            'class' => 'document-upload',
            'data-controller' => 'test-widget',
            'data-label' => '"><script data-test-injected-script>alert(1)</script>',
            'data-ready' => true,
            'data-omitted' => false,
        ] : [];

        $fieldOptions = [
            'attr' => $inputAttributes,
            'widget_attr' => $widgetAttributes,
            'label' => $request->query->getBoolean('row') ? 'Attachments' : null,
            'label_attr' => $request->query->getBoolean('row') ? ['data-test-label' => true] : [],
            'help' => $request->query->getBoolean('row') ? 'Upload one or more documents.' : null,
            'help_attr' => $request->query->getBoolean('row') ? ['data-test-help' => true] : [],
            'row_attr' => $request->query->getBoolean('row') ? ['data-test-row' => true] : [],
            'multiple' => $multiple,
            'max_files' => $multiple ? 5 : 1,
            'auto_upload' => !$request->query->getBoolean('manual'),
            'layout' => $layout,
            'show_preview' => $request->query->getBoolean('preview'),
        ];

        if ($request->query->getBoolean('row')) {
            $form = $this->formFactory->createNamedBuilder('document', FormType::class)
                ->add('attachments', FileUploadType::class, $fieldOptions)
                ->getForm();
            if ($request->query->getBoolean('invalid')) {
                $form->submit(['attachments' => '{invalid']);
            }
            $formView = $form->createView();
            $uploadView = $formView['attachments'];
        } else {
            $form = $this->formFactory->create(FileUploadType::class, null, $fieldOptions);
            if ($request->query->getBoolean('invalid')) {
                $form->submit('{invalid');
            }
            $formView = $form->createView();
            $uploadView = $formView;
        }

        return new Response($this->twig->render('upload_test.html.twig', [
            'form' => $formView,
            'upload_field' => $uploadView,
            'theme' => $theme,
            'stylesheet' => $request->query->getBoolean('styled') ? $layout.'.css' : null,
            'render_row' => $request->query->getBoolean('row'),
            'color_scheme' => \in_array($request->query->getString('scheme'), ['light', 'dark'], true)
                ? $request->query->getString('scheme')
                : null,
        ]));
    }
}
