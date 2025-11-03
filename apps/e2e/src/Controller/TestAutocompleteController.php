<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/test')]
final class TestAutocompleteController extends AbstractController
{
    #[Route('/autocomplete-dynamic-form', name: 'test_autocomplete_dynamic_form')]
    public function dynamicForm(): Response
    {
        return $this->render('test/autocomplete_dynamic_form.html.twig');
    }

    #[Route('/autocomplete/movie', name: 'test_autocomplete_movie')]
    public function movieAutocomplete(Request $request): JsonResponse
    {
        $query = $request->query->get('query', '');

        $movies = [
            ['value' => 'movie_1', 'text' => 'The Matrix (1999)', 'title' => 'movie Movie #1'],
            ['value' => 'movie_2', 'text' => 'Inception (2010)', 'title' => 'movie Movie #2'],
            ['value' => 'movie_3', 'text' => 'The Dark Knight (2008)', 'title' => 'movie Movie #3'],
            ['value' => 'movie_4', 'text' => 'Interstellar (2014)', 'title' => 'movie Movie #4'],
            ['value' => 'movie_5', 'text' => 'Pulp Fiction (1994)', 'title' => 'movie Movie #5'],
        ];

        $results = array_filter($movies, function ($movie) use ($query) {
            return '' === $query || false !== stripos($movie['text'], $query);
        });

        return $this->json([
            'results' => array_values($results),
        ]);
    }

    #[Route('/autocomplete/videogame', name: 'test_autocomplete_videogame')]
    public function videogameAutocomplete(Request $request): JsonResponse
    {
        $query = $request->query->get('query', '');

        $games = [
            ['value' => 'videogame_1', 'text' => 'Halo: Combat Evolved (2001)', 'title' => 'videogame Game #1'],
            ['value' => 'videogame_2', 'text' => 'The Legend of Zelda (1986)', 'title' => 'videogame Game #2'],
            ['value' => 'videogame_3', 'text' => 'Half-Life 2 (2004)', 'title' => 'videogame Game #3'],
            ['value' => 'videogame_4', 'text' => 'Portal (2007)', 'title' => 'videogame Game #4'],
            ['value' => 'videogame_5', 'text' => 'Mass Effect 2 (2010)', 'title' => 'videogame Game #5'],
        ];

        $results = array_filter($games, function ($game) use ($query) {
            return '' === $query || false !== stripos($game['text'], $query);
        });

        return $this->json([
            'results' => array_values($results),
        ]);
    }
}
