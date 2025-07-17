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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Interface for classes that will have an "autocomplete" endpoint exposed.
 *
 * @template T of object
 *
 * TODO Remove next lines for Symfony UX 3
 *
 * @method array getAttributes(object $entity) Returns extra attributes to add to the autocomplete result.
 * @method mixed getGroupBy()                  Return group_by option.
 */
interface EntityAutocompleterInterface
{
    /**
     * The fully-qualified entity class this will be autocompleting.
     *
     * @return class-string<T>
     */
    public function getEntityClass(): string;

    /**
     * Create a query builder that filters for the given "query".
     *
     * @param EntityRepository<T> $repository
     */
    public function createFilteredQueryBuilder(EntityRepository $repository, string $query): QueryBuilder;

    /**
     * Returns the "choice_label" used to display this entity.
     *
     * @param T $entity
     */
    public function getLabel(object $entity): string;

    /**
     * Returns the "value" attribute for this entity, usually the id.
     *
     * @param T $entity
     */
    public function getValue(object $entity): mixed;

    /**
     * Returns extra attributes to add to the autocomplete result.
     *
     * TODO Uncomment for Symfony UX 3
     */
    /* public function getAttributes(object $entity): array; */

    /**
     * Return true if access should be granted to the autocomplete results for the current user.
     *
     * Note: if SecurityBundle is not installed, this will not be called.
     */
    public function isGranted(Security $security): bool;

    /*
     * Return group_by option.
     *
     * TODO Uncomment for Symfony UX 3
     */
    /* public function getGroupBy(): mixed; */
}
