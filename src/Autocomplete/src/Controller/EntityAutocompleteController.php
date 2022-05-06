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
use Symfony\UX\Autocomplete\AutocompleteResultsExecutor;
use Symfony\UX\Autocomplete\AutocompleterRegistry;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 */
final class EntityAutocompleteController
{
    public function __construct(
        private AutocompleterRegistry $autocompleteFieldRegistry,
        private AutocompleteResultsExecutor $autocompleteResultsExecutor
    ) {
    }

    public function __invoke(string $alias, Request $request): Response
    {
        $autocompleter = $this->autocompleteFieldRegistry->getAutocompleter($alias);
        if (!$autocompleter) {
            throw new NotFoundHttpException(sprintf('No autocompleter found for "%s". Available autocompleters are: (%s)', $alias, implode(', ', $this->autocompleteFieldRegistry->getAutocompleterNames())));
        }

        $results = $this->autocompleteResultsExecutor->fetchResults($autocompleter, $request->query->get('query', ''));

        return new JsonResponse([
            'results' => $results,
        ]);
    }
}
