<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Form\AddTodoItemForm;
use App\Form\AnimalCreationForm;
use App\Repository\ChatRepository;
use App\Service\PackageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Turbo\TurboBundle;

class TurboController extends AbstractController
{
    private static array $messages = [
        'Hey!',
        'Hola!',
        'Aloha!',
        'I love pizza!',
        'We should do this again sometime.',
        'Long live donuts!',
        'I deserve a nap.',
        'You\'re great!',
    ];

    #[Route('/turbo', name: 'app_turbo')]
    #[Route('/turbo/{name}/the/{animal}', name: 'app_turbo_with_animal')]
    public function turbo(PackageRepository $packageRepository, ChatRepository $chatRepository, Request $request, string $name = null, string $animal = null): Response
    {
        $form = $this->createForm(AnimalCreationForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            return $this->redirect(
                $this->generateUrl('app_turbo_with_animal', [
                    'name' => $form->get('name')->getData(),
                    'animal' => $form->get('animal')->getData(),
                ])
            );
        }

        return $this->renderForm('turbo/turbo.html.twig', [
            'form' => $form,
            'name' => $name,
            'animal' => $animal,
            'messageChoices' => self::$messages,
            'messages' => $this->getChatMessages($chatRepository),
            'myUsername' => $this->getMyUsername($request->getSession()),
            'package' => $packageRepository->find('turbo'),
        ]);
    }

    #[Route('/turbo/todos', name: 'app_turbo_todo_list')]
    public function turboTodoList(Request $request): Response
    {
        $session = $request->getSession();
        $this->initializeTodos($session);

        return $this->render('turbo/todos.html.twig', [
            'todos' => $session->get('todos'),
        ]);
    }

    #[Route('/turbo/todos/add', name: 'app_turbo_add_todo_item')]
    public function turboAddTodoItem(Request $request): Response
    {
        $session = $request->getSession();
        $this->initializeTodos($session);

        $form = $this->createForm(AddTodoItemForm::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $todos = $session->get('todos');
            $todos[] = $form->get('item')->getData();
            $session->set('todos', $todos);

            return $this->redirect($this->generateUrl('app_turbo_todo_list'));
        }

        return $this->renderForm('turbo/add_todo.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/turbo/todos/remove/{id}', name: 'app_turbo_remove_todo_item')]
    public function removeTodo(string $id, Request $request): Response
    {
        $session = $request->getSession();
        $todos = $session->get('todos');
        unset($todos[$id]);
        $session->set('todos', $todos);

        return $this->redirectToRoute('app_turbo_todo_list');
    }

    #[Route('/turbo/chat', name: 'app_turbo_chat', methods: ['POST'])]
    public function turboChat(Request $request, HubInterface $hub, ChatRepository $chatRepository): Response
    {
        // simple validation. In a real app, if validation fails, we would re-render
        // the page template (e.g. turbo.html.twig) that contains the form, and
        // allow it to render with errors. In other words, like any other form submit.
        if (!$request->request->has('chat_message')) {
            return new Response(null, 204);
        }

        $message = self::$messages[$request->request->get('chat_message')] ?? 'Unknown Message!';

        $chat = new Chat();
        $chat->setUsername($this->getMyUsername($request->getSession()));
        $chat->setMessage($message);
        $chatRepository->add($chat, true);

        // send an update to ALL users viewing this page to add the new chat & update header count
        $hub->publish(new Update(
            'chat',
            $this->renderView('turbo/all_users_chat_success.stream.html.twig', [
                'messages' => $this->getChatMessages($chatRepository),
                'messageCount' => $chatRepository->count([]),
            ])
        ));

        // this stream update is ONLY for the user submitting the form: it "resets" the form
        if (TurboBundle::STREAM_FORMAT === $request->getPreferredFormat()) {
            $request->setRequestFormat(TurboBundle::STREAM_FORMAT);

            return $this->render('turbo/chat_success.stream.html.twig', [
                'messageChoices' => self::$messages,
            ]);
        }

        // If the client doesn't support JavaScript, just redirect
        // Turbo is all about progressive enhancement!
        return $this->redirectToRoute('app_turbo');
    }

    private function initializeTodos(SessionInterface $session): void
    {
        if (!$session->has('todos')) {
            $session->set('todos', [
                'Pour a delicious cup of coffee',
                'Feed my pet 🐢 Frank',
            ]);
        }
    }

    private function getMyUsername(SessionInterface $session): string
    {
        if (!$session->has('username')) {
            $session->set('username', 'user_'.rand(100, 999));
        }

        return $session->get('username');
    }

    /**
     * Finds the most recent messages from the database, then reverses
     * them so that the newest show at the bottom.
     *
     * @return array<Chat>
     */
    private function getChatMessages(ChatRepository $chatRepository): array
    {
        $chats = $chatRepository->findBy([], ['createdAt' => 'DESC'], 20);

        return array_reverse($chats);
    }
}
