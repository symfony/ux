<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ux-live-component', name: 'app_ux_live_component_')]
final class LiveComponentController extends AbstractController
{
    #[Route('/counter', name: 'counter')]
    public function counter(): Response
    {
        return $this->render('ux_live_component/counter.html.twig');
    }

    #[Route('/registration-form', name: 'registration_form')]
    public function registrationForm(): Response
    {
        return $this->render('ux_live_component/registration_form.html.twig');
    }

    #[Route('/fruits/{page?1}', name: 'fruits')]
    public function fruits(int $page): Response
    {
        return $this->render('ux_live_component/fruits.html.twig', [
            'page' => $page,
        ]);
    }

    #[Route('/with-dto', name: 'with_dto')]
    public function withDto(): Response
    {
        return $this->render('ux_live_component/with_dto.html.twig');
    }

    #[Route('/with-dto-collection', name: 'with_dto_collection')]
    public function withDtoCollection(): Response
    {
        return $this->render('ux_live_component/with_dto_collection.html.twig');
    }

    #[Route('/with-dto-and-serializer', name: 'with_dto_and_serializer')]
    public function withDtoAndSerializer(): Response
    {
        return $this->render('ux_live_component/with_dto_and_serializer.html.twig');
    }

    #[Route('/with-dto-and-custom-hydration-methods', name: 'with_dto_and_custom_hydration_methods')]
    public function withDtoAndCustomHydrationMethods(): Response
    {
        return $this->render('ux_live_component/with_dto_and_custom_hydration_methods.html.twig');
    }

    #[Route('/with-dto-and-hydration-extension', name: 'with_dto_and_hydration_extension')]
    public function withDtoAndHydrationExtension(): Response
    {
        return $this->render('ux_live_component/with_dto_and_hydration_extension.html.twig');
    }

    #[Route('/item-list', name: 'item_list')]
    public function itemList(): Response
    {
        return $this->render('ux_live_component/item_list.html.twig');
    }

    #[Route('/with-aliased-live-props', name: 'with_aliased_live_props')]
    public function withAliasedLiveProps(): Response
    {
        return $this->render('ux_live_component/with_aliased_live_props.html.twig');
    }
}
