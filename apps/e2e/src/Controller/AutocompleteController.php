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

use App\Form\FruitAutocompleteField;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-autocomplete', name: 'app_ux_autocomplete_')]
final class AutocompleteController extends AbstractController
{
    #[Route('/without-ajax', name: 'without_ajax')]
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
            'form' => $form->createView(),
        ]);
    }

    #[Route('/with-ajax', name: 'with_ajax')]
    public function withAjax(): Response
    {
        $formBuilder = $this->createFormBuilder();
        $formBuilder->add('favorite_fruit', FruitAutocompleteField::class);

        $form = $formBuilder->getForm();

        return $this->render('ux_autocomplete/with_ajax.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/custom-controller', name: 'custom_controller')]
    public function customController(): Response
    {
        return $this->render('ux_autocomplete/custom_controller.html.twig');
    }
}
