<?php

namespace App\Controller;

use App\Entity\TodoItem;
use App\Entity\TodoList;
use App\Form\TodoListForm;
use App\Repository\FoodRepository;
use App\Repository\TodoListRepository;
use App\Service\LiveDemoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        $form = $this->createForm(TodoListForm::class, $todoList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $todoListRepository->add($form->getData(), true);
            $this->addFlash('live_demo_success', 'Excellent! With this to-do list, I won’t miss anything.');

            return $this->redirectToRoute('app_live_components_demo_form_collection_type', [
                'id' => $todoList->getId(),
            ]);
        }

        return $this->renderForm('live_component_demo/form_collection_type.html.twig', [
            'form' => $form,
            'todo' => $todoList,
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
}
