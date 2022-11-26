<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\UX\Autocomplete\Doctrine\DoctrineRegistryWrapper;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 */
final class AutocompleteResultsExecutor
{
    public function __construct(
        private DoctrineRegistryWrapper $managerRegistry,
        private ?Security $security = null
    ) {
    }

    public function fetchResults(EntityAutocompleterInterface $autocompleter, string $query, int $page): AutocompleteResults
    {
        if ($this->security && !$autocompleter->isGranted($this->security)) {
            throw new AccessDeniedException('Access denied from autocompleter class.');
        }

        $queryBuilder = $autocompleter->createFilteredQueryBuilder(
            $this->managerRegistry->getRepository($autocompleter->getEntityClass()),
            $query
        );

        // if no max is set, set one
        if (!$queryBuilder->getMaxResults()) {
            $queryBuilder->setMaxResults(10);
        }

        $page = max(1, $page);

        $queryBuilder->setFirstResult(($page - 1) * $queryBuilder->getMaxResults());

        $paginator = new Paginator($queryBuilder);

        $nbPages = (int) ceil($paginator->count() / $queryBuilder->getMaxResults());

        $results = [];
        foreach ($paginator as $entity) {
            $results[] = [
                'value' => $autocompleter->getValue($entity),
                'text' => $autocompleter->getLabel($entity),
            ];
        }

        return new AutocompleteResults($results, $page < $nbPages);
    }
}
