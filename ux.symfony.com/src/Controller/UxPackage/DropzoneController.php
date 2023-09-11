<?php

namespace App\Controller\UxPackage;

use App\Form\DropzoneForm;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DropzoneController extends AbstractController
{
    #[Route('/dropzone', name: 'app_dropzone')]
    public function __invoke(UxPackageRepository $packageRepository, Request $request): Response
    {
        $package = $packageRepository->find('dropzone');

        $form = $this->createForm(DropzoneForm::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->addFlash('dropzone_success', 'File uploaded! Then immediately discarded... since this is a demo server.');

            return $this->redirectToRoute('app_dropzone');
        }

        return $this->render('ux_packages/dropzone.html.twig', [
            'package' => $package,
            'form' => $form,
        ]);
    }
}
