<?php

namespace App\Controller;

use App\Entity\Invoice;
use App\Entity\TodoItem;
use App\Entity\TodoList;
use App\Form\TodoListFormType;
use App\Repository\FoodRepository;
use App\Repository\TodoListRepository;
use App\Service\DinoPager;
use App\Service\LiveDemoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/live-component/demos')]
class LiveComponentDemoController extends AbstractController
{
    #[Route('/auto-validating-form', name: 'app_live_components_demo_auto_validating_form')]
    public function demoAutoValidatingForm(LiveDemoRepository $liveDemoRepository): Response
    {
        return $this->render('live_component_demo/auto_validating_form.html.twig', [
            'demo' => $liveDemoRepository->find('auto-validating-form'),
        ]);
    }

    #[Route('/form-collection-type/{id}', name: 'app_live_components_demo_form_collection_type', defaults: ['id' => null])]
    public function demoFormCollectionType(LiveDemoRepository $liveDemoRepository, Request $request, TodoListRepository $todoListRepository, TodoList $todoList = null): Response
    {
        if (!$todoList) {
            $todoList = new TodoList();
            $todoList->addTodoItem(new TodoItem());
        }
        $form = $this->createForm(TodoListFormType::class, $todoList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $todoListRepository->add($form->getData(), true);
            $this->addFlash('live_demo_success', 'Excellent! With this to-do list, I won’t miss anything.');

            return $this->redirectToRoute('app_live_components_demo_form_collection_type', [
                'id' => $todoList->getId(),
            ]);
        }

        return $this->render('live_component_demo/form_collection_type.html.twig', [
            'form' => $form,
            'todoList' => $todoList,
            'demo' => $liveDemoRepository->find('form-collection-type'),
        ]);
    }

    #[Route('/dependent-form-fields', name: 'app_live_components_demo_dependent_form_fields')]
    public function demoDependentFormFields(LiveDemoRepository $liveDemoRepository): Response
    {
        return $this->render('live_component_demo/dependent_form_fields.html.twig', [
            'demo' => $liveDemoRepository->find('dependent-form-fields'),
        ]);
    }

    #[Route('/voting', name: 'app_live_components_demo_voting')]
    public function demoVoting(LiveDemoRepository $liveDemoRepository, FoodRepository $foodRepository): Response
    {
        return $this->render('live_component_demo/voting.html.twig', [
            'demo' => $liveDemoRepository->find('voting'),
            'foods' => $foodRepository->findAll(),
        ]);
    }

    #[Route('/inline-edit', name: 'app_live_components_demo_inline_edit')]
    public function inlineEdit(LiveDemoRepository $liveDemoRepository, FoodRepository $foodRepository): Response
    {
        $food = $foodRepository->findOneBy([]);
        if (!$food) {
            throw $this->createNotFoundException('No food found - try running "php bin/console app:load-data"');
        }

        return $this->render('live_component_demo/inline_edit.html.twig', parameters: [
            'demo' => $liveDemoRepository->find('inline_edit'),
            'food' => $foodRepository->findOneBy([]),
        ]);
    }

    #[Route('/chartjs', name: 'app_live_components_demo_chartjs')]
    public function chartJs(LiveDemoRepository $liveDemoRepository): Response
    {
        return $this->render('live_component_demo/chartjs.html.twig', parameters: [
            'demo' => $liveDemoRepository->find('chartjs_updating'),
        ]);
    }

    #[Route('/invoice/{id}', name: 'app_live_components_invoice', defaults: ['id' => null])]
    public function invoice(LiveDemoRepository $liveDemoRepository, Invoice $invoice = null): Response
    {
        $invoice = $invoice ?? new Invoice();

        return $this->render('live_component_demo/invoice.html.twig', parameters: [
            'demo' => $liveDemoRepository->find('invoice'),
            'invoice' => $invoice,
        ]);
    }

    #[Route('/product-form', name: 'app_live_components_product_form')]
    public function productForm(LiveDemoRepository $liveDemoRepository): Response
    {
        return $this->render('live_component_demo/product_form.html.twig', parameters: [
            'demo' => $liveDemoRepository->find('product_form'),
        ]);
    }

    #[Route('/paginated-list', name: 'app_live_components_paginated_list')]
    public function paginatedList(LiveDemoRepository $liveDemoRepository, Request $request, DinoPager $pager): Response
    {
        $form = $this->createFormBuilder(null, ['method' => 'GET'])
            ->add('name', TextType::class, [
                'required' => false,
            ])
            ->add('type', TextType::class, [
                'required' => false,
            ])->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $pager->filter(name: $data['name'], type: $data['type']);
        }

        $pager->setCurrentPage($request->query->get('page', '1'));

        return $this->render('live_component_demo/paginated_list.html.twig', parameters: [
            'demo' => $liveDemoRepository->find('paginated_list'),
            'form' => $form,
            'pager' => $pager,
        ]);
    }
}
