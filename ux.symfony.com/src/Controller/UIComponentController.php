<?php

namespace App\Controller;

use App\UIComponent\UIComponentCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class UIComponentController extends AbstractController
{
    #[Route('/components', name: 'app_ui_component_index')]
    public function index(UIComponentCollection $componentCollection)
    {
        return $this->render('ui_component/index.html.twig', [
            'components' => $componentCollection,
        ]);
    }

    #[Route('/components/{key}', name: 'app_ui_component_show')]
    public function show(string $key, UIComponentCollection $componentCollection)
    {
        $component = $componentCollection->get($key);
        if (!$component) {
            throw $this->createNotFoundException(sprintf('Component with key "%s" not found', $key));
        }

        return $this->render('ui_component/'.$component->getTemplate(), [
            'component' => $component,
        ]);
    }
}
