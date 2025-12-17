<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Cropperjs\Factory\CropperInterface;
use Symfony\UX\Cropperjs\Form\CropperType;

#[Route('/ux-cropperjs', name: 'app_ux_cropperjs_')]
final class CropperjsController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public')]
        private string $publicDir
    ) {}

    #[Route('/crop', name: 'crop')]
    public function crop(CropperInterface $cropper, Request $request): Response
    {
        $crop = $cropper->createCrop($this->publicDir . '/images/example.jpg');
        $crop->setCroppedMaxSize(800, 600);

        $form = $this->createFormBuilder(['crop' => $crop])
            ->add('crop', CropperType::class, [
                'public_url' => '/images/example.jpg',
                'cropper_options' => [
                    'viewMode' => 1,
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        $croppedImageData = null;
        if ($form->isSubmitted() && $form->isValid()) {
            // Get the cropped image as base64
            $croppedImageData = base64_encode($crop->getCroppedImage());
        }

        return $this->render('ux_cropperjs/crop.html.twig', [
            'form' => $form,
            'croppedImageData' => $croppedImageData,
        ]);
    }

    #[Route('/crop-with-aspect-ratio', name: 'crop_with_aspect_ratio')]
    public function cropWithAspectRatio(CropperInterface $cropper, Request $request): Response
    {
        $crop = $cropper->createCrop($this->publicDir . '/images/example.jpg');
        $crop->setCroppedMaxSize(1920, 1080);

        $form = $this->createFormBuilder(['crop' => $crop])
            ->add('crop', CropperType::class, [
                'public_url' => '/images/example.jpg',
                'cropper_options' => [
                    'aspectRatio' => 16 / 9,
                    'viewMode' => 1,
                ],
            ])
            ->getForm();

        $form->handleRequest($request);

        $croppedImageData = null;
        if ($form->isSubmitted() && $form->isValid()) {
            // Get the cropped image as base64
            $croppedImageData = base64_encode($crop->getCroppedImage());
        }

        return $this->render('ux_cropperjs/crop.html.twig', [
            'form' => $form,
            'croppedImageData' => $croppedImageData,
        ]);
    }
}
