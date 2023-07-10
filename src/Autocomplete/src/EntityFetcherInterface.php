<?php

namespace Symfony\UX\Autocomplete;

interface EntityFetcherInterface
{
    /**
     * Fetch multiple entities from their identifiers.
     *
     * @template T of object
     *
     * @param class-string<T> $entityClass
     * @param mixed[]         $identifiers the entity identifiers
     *
     * @return T[]
     */
    public function fetchMultipleEntities(string $entityClass, array $identifiers): array;

    /**
     * Fetch a single entity from its identifier.
     *
     * @template T of object
     *
     * @param class-string<T> $entityClass
     * @param mixed           $identifier  the entity identifier
     *
     * @return T|null
     */
    public function fetchSingleEntity(string $entityClass, mixed $identifier): ?object;
}
