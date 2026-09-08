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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Dropzone\Form\DropzoneType;

#[Route('/ux-dropzone', name: 'app_ux_dropzone_')]
final class DropzoneController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('ux_dropzone/index.html.twig', [
            'controller_name' => 'DropzoneController',
        ]);
    }

    #[Route('/multiple', name: 'multiple')]
    public function multiple(Request $request): Response
    {
        $form = $this->createFormBuilder()
            ->add('photos', DropzoneType::class, [
                'multiple' => true,
                'required' => false,
            ])
            ->getForm()
        ;

        $form->handleRequest($request);

        $uploadedFiles = [];
        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('photos')->getData() ?? [] as $file) {
                $uploadedFiles[] = $file->getClientOriginalName();
            }
        }

        return $this->render('ux_dropzone/multiple.html.twig', [
            'form' => $form,
            'uploadedFiles' => $uploadedFiles,
        ]);
    }
}
