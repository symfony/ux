<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests\Fixtures;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\Pagination\LiveComponent\ComponentWithPaginationTrait;
use Symfony\UX\Pagination\PaginationBuilder;
use Symfony\UX\Pagination\PaginatorInterface;

#[AsLiveComponent('live_pagination_auto', template: 'components/live_pagination.html.twig')]
final class LiveAutoRoutePaginationComponent
{
    use ComponentWithPaginationTrait;
    use DefaultActionTrait;

    public function __construct(
        private readonly PaginatorInterface $paginator,
    ) {
    }

    protected function createPagination(): PaginationBuilder
    {
        return $this->paginator
            ->query(range(1, 30))
            ->perPage(10);
    }
}
