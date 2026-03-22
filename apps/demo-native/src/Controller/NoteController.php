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

final class NoteController extends AbstractController
{
    private const NOTES = [
        1 => [
            'id' => 1,
            'title' => 'Weekly grocery list',
            'content' => "- Eggs\n- Milk\n- Bread\n- Fresh vegetables\n- Chicken breast\n- Olive oil\n- Pasta\n- Tomato sauce",
            'category' => 'Personal',
            'color' => 'primary',
            'date' => '2026-03-22',
        ],
        2 => [
            'id' => 2,
            'title' => 'Sprint planning notes',
            'content' => "Focus this sprint:\n1. Finish user authentication module\n2. Fix the pagination bug on the dashboard\n3. Write integration tests for the API\n4. Code review for the new search feature",
            'category' => 'Work',
            'color' => 'success',
            'date' => '2026-03-21',
        ],
        3 => [
            'id' => 3,
            'title' => 'Book recommendations',
            'content' => "- \"Designing Data-Intensive Applications\" by Martin Kleppmann\n- \"The Pragmatic Programmer\" by Andy Hunt & Dave Thomas\n- \"Qualite logicielle pour les developpeurs back-end\" by Jean-Francois Lepine",
            'category' => 'Reading',
            'color' => 'warning',
            'date' => '2026-03-20',
        ],
        4 => [
            'id' => 4,
            'title' => 'Vacation ideas',
            'content' => "Possible destinations:\n- Japan (Tokyo + Kyoto)\n- South Korea (Seoul)\n- Canada (Montreal — SymfonyDay!)\n\nBudget: around 2500 EUR per person",
            'category' => 'Personal',
            'color' => 'info',
            'date' => '2026-03-19',
        ],
        5 => [
            'id' => 5,
            'title' => 'Symfony UX Native demo ideas',
            'content' => "Things to showcase:\n- Path configuration (modal vs default context)\n- Conditional rendering with ux_is_native()\n- Pull-to-refresh support\n- Bridge components for native UI elements",
            'category' => 'Work',
            'color' => 'danger',
            'date' => '2026-03-18',
        ],
        6 => [
            'id' => 6,
            'title' => 'Fitness goals Q2 2026',
            'content' => "- Run 3x per week (5km minimum)\n- Strength training 2x per week\n- Stretch every morning\n- Track daily water intake (2L minimum)",
            'category' => 'Health',
            'color' => 'secondary',
            'date' => '2026-03-17',
        ],
    ];

    #[Route('/', name: 'app_notes')]
    public function index(): Response
    {
        return $this->render('note/index.html.twig', [
            'notes' => self::NOTES,
        ]);
    }

    #[Route('/notes/{id}', name: 'app_note_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $note = self::NOTES[$id] ?? null;

        if (!$note) {
            throw $this->createNotFoundException('Note not found.');
        }

        return $this->render('note/show.html.twig', [
            'note' => $note,
        ]);
    }

    #[Route('/notes/new', name: 'app_note_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Note created successfully!');

            return $this->redirectToRoute('app_notes');
        }

        return $this->render('note/new.html.twig');
    }

    #[Route('/notes/{id}/edit', name: 'app_note_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, int $id): Response
    {
        $note = self::NOTES[$id] ?? null;

        if (!$note) {
            throw $this->createNotFoundException('Note not found.');
        }

        if ($request->isMethod('POST')) {
            $this->addFlash('success', 'Note updated successfully!');

            return $this->redirectToRoute('app_note_show', ['id' => $id]);
        }

        return $this->render('note/edit.html.twig', [
            'note' => $note,
        ]);
    }
}
