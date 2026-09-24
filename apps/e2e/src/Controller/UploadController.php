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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\UX\Upload\Form\UploadType;
use Symfony\UX\Upload\Upload\UploadedFile;

/**
 * Pages driven by the UX Upload browser tests.
 */
#[Route('/ux-upload', name: 'app_ux_upload_')]
final class UploadController extends AbstractController
{
    #[Route('/', name: 'single')]
    public function single(Request $request): Response
    {
        return $this->handleUpload($request, 'attachments', ['label' => 'Attachments']);
    }

    #[Route('/multiple', name: 'multiple')]
    public function multiple(Request $request): Response
    {
        return $this->handleUpload($request, 'attachments', ['label' => 'Attachments', 'multiple' => true]);
    }

    #[Route('/manual', name: 'manual')]
    public function manual(Request $request): Response
    {
        return $this->handleUpload($request, 'attachments', ['label' => 'Attachments', 'auto_upload' => false, 'required' => false]);
    }

    #[Route('/minimal-theme', name: 'minimal_theme')]
    public function minimalTheme(Request $request): Response
    {
        return $this->handleUpload($request, 'attachments', ['label' => 'Attachments'], formTheme: 'ux_upload/minimal_theme.html.twig');
    }

    #[Route('/form', name: 'form')]
    public function form(Request $request): Response
    {
        return $this->handleUpload($request, 'attachment', [
            'label' => 'Attachment',
            'uploader' => 'chunked',
            'max_size' => '1M',
        ], [
            'title' => [TextType::class, [
                'required' => false,
                'constraints' => [new NotBlank()],
                'row_attr' => ['class' => 'mb-3'],
                'label_attr' => ['class' => 'form-label'],
                'attr' => ['class' => 'form-control'],
            ]],
        ]);
    }

    #[Route('/gallery', name: 'gallery')]
    public function gallery(Request $request): Response
    {
        return $this->handleUpload($request, 'photos', [
            'label' => 'Photos',
            'layout' => 'dropzone',
            'show_preview' => true,
            'multiple' => true,
            'max_files' => 3,
        ]);
    }

    #[Route('/validation', name: 'validation')]
    public function validation(Request $request): Response
    {
        return $this->handleUpload($request, 'image', ['label' => 'Image', 'uploader' => 'images']);
    }

    /**
     * @param array<string, mixed>                                     $options
     * @param array<string, array{class-string, array<string, mixed>}> $fields  added before the upload field
     */
    private function handleUpload(Request $request, string $field, array $options, array $fields = [], ?string $formTheme = null): Response
    {
        $builder = $this->createFormBuilder();
        foreach ($fields as $name => [$type, $fieldOptions]) {
            $builder->add($name, $type, $fieldOptions);
        }
        $form = $builder->add($field, UploadType::class, $options)->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->get($field)->getData();
            $uploads = \is_array($data) ? $data : array_filter([$data]);
            $this->addFlash('upload_received', array_map(fn (UploadedFile $upload): array => [
                'name' => $upload->getClientOriginalName(),
                'size' => $upload->getSize(),
                'mime_type' => $upload->getMimeType(),
                'sha256' => $this->sha256($upload),
            ], array_values($uploads)));

            return $this->redirectToRoute($request->attributes->getString('_route'));
        }

        return $this->render('ux_upload/index.html.twig', [
            'form' => $form,
            'upload_field' => $field,
            'form_theme' => $formTheme,
        ]);
    }

    private function sha256(UploadedFile $upload): string
    {
        $stream = $upload->openStream();
        try {
            $context = hash_init('sha256');
            hash_update_stream($context, $stream);

            return hash_final($context);
        } finally {
            fclose($stream);
        }
    }
}
