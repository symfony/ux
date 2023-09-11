<?php

namespace App\Controller\UxPackage;

use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Cropperjs\Factory\CropperInterface;
use Symfony\UX\Cropperjs\Form\CropperType;

class CropperjsController extends AbstractController
{
    public function __construct(
        private Packages $assets,
        private string $projectDir
    ) {
    }

    #[Route('/cropperjs', name: 'app_cropperjs')]
    public function __invoke(UxPackageRepository $packageRepository, CropperInterface $cropper, Request $request): Response
    {
        $package = $packageRepository->find('cropperjs');

        $crop = $cropper->createCrop($this->projectDir.'/assets/images/large.jpg');
        $crop->setCroppedMaxSize(1000, 750);

        $form = $this->createFormBuilder(['crop' => $crop])
            ->add('crop', CropperType::class, [
                'public_url' => $this->assets->getUrl('images/large.jpg'),
                'cropper_options' => [
                    'aspectRatio' => 4 / 3,
                    'preview' => '#cropper-preview',
                    'scalable' => false,
                    'zoomable' => false,
                ],
            ])
            ->getForm();

        $form->handleRequest($request);
        $croppedImage = null;
        $croppedThumbnail = null;
        if ($form->isSubmitted()) {
            // faking an error to let the page re-render with the cropped images
            $form->addError(new FormError('🤩'));
            $croppedImage = sprintf('data:image/jpeg;base64,%s', base64_encode($crop->getCroppedImage()));
            $croppedThumbnail = sprintf('data:image/jpeg;base64,%s', base64_encode($crop->getCroppedThumbnail(200, 150)));
        }

        return $this->render('ux_packages/cropperjs.html.twig', [
            'package' => $package,
            'form' => $form,
            'croppedImage' => $croppedImage,
            'croppedThumbnail' => $croppedThumbnail,
        ]);
    }
}
