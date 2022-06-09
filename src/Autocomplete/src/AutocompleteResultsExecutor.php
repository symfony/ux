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

use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Security;
use Symfony\UX\Autocomplete\Doctrine\DoctrineRegistryWrapper;
use Symfony\UX\Autocomplete\Doctrine\EntitySearchUtil;

/**
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 */
final class AutocompleteResultsExecutor
{
    public function __construct(
        private EntitySearchUtil $entitySearchUtil,
        private DoctrineRegistryWrapper $managerRegistry,
        private ?Security $security = null
    ) {
    }

    public function fetchResults(EntityAutocompleterInterface $autocompleter, string $query): array
    {
        if ($this->security && !$autocompleter->isGranted($this->security)) {
            throw new AccessDeniedException('Access denied from autocompleter class.');
        }

        $queryBuilder = $autocompleter->getQueryBuilder($this->managerRegistry->getRepository($autocompleter->getEntityClass()));
        $searchableProperties = $autocompleter->getSearchableFields();
        $this->entitySearchUtil->addSearchClause($queryBuilder, $query, $autocompleter->getEntityClass(), $searchableProperties);

        // if no max is set, set one
        if (!$queryBuilder->getMaxResults()) {
            $queryBuilder->setMaxResults(10);
        }

        $entities = $queryBuilder->getQuery()->execute();

        $results = [];
        foreach ($entities as $entity) {
            $results[] = [
                'value' => $autocompleter->getValue($entity),
                'text' => $autocompleter->getLabel($entity),
            ];
        }

        return $results;
    }
}
