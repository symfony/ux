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

use Symfony\Bundle\SecurityBundle\Security;

/**
 * Interface for classes that will have an "autocomplete" endpoint exposed,
 * without requiring Doctrine ORM.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
interface AutocompleterInterface
{
    /**
     * Fetch autocomplete results for the given query and page.
     */
    public function fetchResults(string $query, int $page): AutocompleteResults;

    /**
     * Return true if access should be granted to the autocomplete results for the current user.
     *
     * Note: if SecurityBundle is not installed, this will not be called.
     */
    public function isGranted(Security $security): bool;
}
