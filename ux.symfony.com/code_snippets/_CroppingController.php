<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Cropperjs\Factory\CropperInterface;
use Symfony\UX\Cropperjs\Form\CropperType;

class _CroppingController extends AbstractController
{
    #[Route('/cropperjs')]
    public function cropper(CropperInterface $cropper, Request $request)
    {
        $imagePath = $this->getParameter('kernel.project_dir').'/public/uploads/some_file.png';
        $crop = $cropper->createCrop($imagePath);

        $form = $this->createFormBuilder(['crop' => $crop])
            ->add('crop', CropperType::class, [
                'public_url' => '/uploads/some_file.png',
            ])->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // save cropped image contents to a file
            $cropped = $crop->getCroppedImage();
            $croppedThumbnail = $crop->getCroppedThumbnail(200, 150);
        }

        // ... render template
    }
}
