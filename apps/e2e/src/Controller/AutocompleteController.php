<?php

namespace App\Controller;

use App\Form\Type\AutocompleteSelectType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-autocomplete')]
final class AutocompleteController extends AbstractController
{
    #[Route('/without-ajax')]
    public function index()
    {
        $form = $this->createForm(AutocompleteSelectType::class);

        return $this->render(
            'ux_autocomplete/index.html.twig',
            ['form' => $form->createView()]
        );
    }

    #[Route('/custom-controller')]
    public function customController()
    {
        return $this->render('ux_autocomplete/custom_controller.html.twig');
    }
}
