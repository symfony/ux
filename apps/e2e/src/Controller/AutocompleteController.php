<?php

namespace App\Controller;

use App\Form\FruitAutocompleteField;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-autocomplete')]
final class AutocompleteController extends AbstractController
{
    #[Route('/without-ajax')]
    public function withoutAjax(): Response
    {
        $formBuilder = $this->createFormBuilder();
        $formBuilder->add('favorite_fruit', ChoiceType::class, [
            'autocomplete' => true,
            'label' => 'Your favorite fruit:',
            'choices' => [
                'Apple' => 'apple',
                'Banana' => 'banana',
                'Cherry' => 'cherry',
                'Coconut' => 'coconut',
                'Grape' => 'grape',
                'Kiwi' => 'kiwi',
                'Lemon' => 'lemon',
                'Mango' => 'mango',
                'Orange' => 'orange',
                'Papaya' => 'papaya',
                'Peach' => 'peach',
                'Pineapple' => 'pineapple',
                'Pear' => 'pear',
                'Pomegranate' => 'pomegranate',
                'Pomelo' => 'pomelo',
                'Raspberry' => 'raspberry',
                'Strawberry' => 'strawberry',
                'Watermelon' => 'watermelon',
            ],
        ]);

        $form = $formBuilder->getForm();

        return $this->render('ux_autocomplete/without_ajax.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/with-ajax')]
    public function withAjax(): Response
    {
        $formBuilder = $this->createFormBuilder();
        $formBuilder->add('favorite_fruit', FruitAutocompleteField::class);

        $form = $formBuilder->getForm();

        return $this->render('ux_autocomplete/with_ajax.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/custom-controller')]
    public function customController(): Response
    {
        return $this->render('ux_autocomplete/custom_controller.html.twig');
    }
}
