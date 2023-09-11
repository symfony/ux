<?php

namespace App\Controller\UxPackage;

use App\Form\TogglePasswordForm;
use App\Service\UxPackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TogglePasswordController extends AbstractController
{
    #[Route('/toggle-password', name: 'app_toggle_password')]
    public function __invoke(UxPackageRepository $packageRepository): Response
    {
        $package = $packageRepository->find('toggle-password');

        return $this->render('ux_packages/toggle_password.html.twig', [
            'package' => $package,
            'form' => $this->createForm(TogglePasswordForm::class),
        ]);
    }
}
