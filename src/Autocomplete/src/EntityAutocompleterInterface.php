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
 */
interface EntityAutocompleterInterface
{
    /**
     * The fully-qualified entity class this will be autocompleting.
     */
    public function getEntityClass(): string;

    /**
     * A query builder that would return all potential results.
     */
    public function getQueryBuilder(EntityRepository $repository): QueryBuilder;

    /**
     * Returns the "choice_label" used to display this entity.
     */
    public function getLabel(object $entity): string;

    /**
     * Returns the "value" attribute for this entity, usually the id.
     */
    public function getValue(object $entity): mixed;

    /**
     * Return an array of the fields to search.
     *
     * If null is returned, all fields are searched.
     */
    public function getSearchableFields(): ?array;

    /**
     * Return true if access should be granted to the autocomplete results for the current user.
     *
     * Note: if SecurityBundle is not installed, this will not be called.
     */
    public function isGranted(Security $security): bool;
}
