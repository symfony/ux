<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Autocomplete\AutocompleteResultsExecutor;
use Symfony\UX\Autocomplete\AutocompleterRegistry;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class EntityAutocompleteController
{
    public function __construct(
        private AutocompleterRegistry $autocompleteFieldRegistry,
        private AutocompleteResultsExecutor $autocompleteResultsExecutor,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(string $alias, Request $request): Response
    {
        $autocompleter = $this->autocompleteFieldRegistry->getAutocompleter($alias);
        if (!$autocompleter) {
            throw new NotFoundHttpException(sprintf('No autocompleter found for "%s". Available autocompleters are: (%s)', $alias, implode(', ', $this->autocompleteFieldRegistry->getAutocompleterNames())));
        }

        $page = $request->query->getInt('page', 1);
        $nextPage = null;

        $data = $this->autocompleteResultsExecutor->fetchResults($autocompleter, $request->query->get('query', ''), $page);

        if ($data->hasNextPage) {
            $parameters = array_merge($request->attributes->all('_route_params'), $request->query->all(), ['page' => $page + 1]);

            $nextPage = $this->urlGenerator->generate($request->attributes->get('_route'), $parameters);
        }

        return new JsonResponse([
            'results' => ($data->optgroups) ? ['options' => $data->results, 'optgroups' => $data->optgroups] : $data->results,
            'next_page' => $nextPage,
        ]);
    }
}
