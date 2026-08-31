<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Upload\Form\FileUploadType;

/**
 * Pages driven by the UX Upload browser tests.
 *
 * Each route is one scenario the Playwright specs need a real browser for:
 * a full upload round-trip, several files at once, the manual start button,
 * and a form theme that empties the optional presentation blocks.
 */
#[Route('/ux-upload', name: 'app_ux_upload_')]
final class UploadController extends AbstractController
{
    #[Route('/', name: 'single')]
    public function single(): Response
    {
        return $this->renderUpload();
    }

    #[Route('/multiple', name: 'multiple')]
    public function multiple(): Response
    {
        return $this->renderUpload(['multiple' => true, 'max_files' => 5]);
    }

    #[Route('/manual', name: 'manual')]
    public function manual(): Response
    {
        return $this->renderUpload(['auto_upload' => false]);
    }

    #[Route('/minimal-theme', name: 'minimal_theme')]
    public function minimalTheme(): Response
    {
        return $this->renderUpload(formTheme: 'ux_upload/minimal_theme.html.twig');
    }

    /**
     * @param array<string, mixed> $options
     */
    private function renderUpload(array $options = [], ?string $formTheme = null): Response
    {
        $form = $this->createFormBuilder()
            ->add('attachments', FileUploadType::class, $options + [
                'label' => 'Attachments',
            ])
            ->getForm();

        return $this->render('ux_upload/index.html.twig', [
            'form' => $form->createView(),
            'form_theme' => $formTheme,
        ]);
    }
}
