<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Adapter;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Common contract for pagination data source adapters.
 *
 * Implement one or more capability interfaces to add offset, lookahead or
 * cursor pagination for a data source. Adapters are auto-discovered by the
 * container via the service tag.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
#[AutoconfigureTag('ux_pagination.adapter')]
interface PaginationAdapterInterface
{
    /**
     * Whether this adapter can handle the given source.
     */
    public function supports(mixed $source): bool;
}
