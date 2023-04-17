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
use Symfony\Component\Security\Core\Security;

/**
 * Interface for classes that will have an "autocomplete" endpoint exposed.
 *
 * @method mixed getGroupBy() Return group_by option.
 */
interface EntityAutocompleterInterface
{
    /**
     * The fully-qualified entity class this will be autocompleting.
     */
    public function getEntityClass(): string;

    /**
     * Create a query builder that filters for the given "query".
     */
    public function createFilteredQueryBuilder(EntityRepository $repository, string $query): QueryBuilder;

    /**
     * Returns the "choice_label" used to display this entity.
     */
    public function getLabel(object $entity): string;

    /**
     * Returns the "value" attribute for this entity, usually the id.
     */
    public function getValue(object $entity): mixed;

    /**
     * Return true if access should be granted to the autocomplete results for the current user.
     *
     * Note: if SecurityBundle is not installed, this will not be called.
     */
    public function isGranted(Security $security): bool;

    /*
     * Return group_by option.
     */
    /* public function getGroupBy(): mixed; */
}
