<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination;

use Symfony\UX\Pagination\Navigation\Navigation;

/**
 * Contract for numbered pagination results.
 *
 * @template T
 *
 * @extends PaginationInterface<T>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface NumberedPaginationInterface extends PaginationInterface
{
    /**
     * @return int<1, max>
     */
    public function getCurrentPage(): int;

    public function getPageParameterName(): string;

    /**
     * Return the exact total, or null for lookahead pagination.
     *
     * @return int<0, max>|null
     */
    public function getTotalItems(): ?int;

    /**
     * Return the exact number of pages, or null for lookahead pagination.
     *
     * @return int<1, max>|null
     */
    public function getTotalPages(): ?int;

    /**
     * Return the one-based position of the first item on this page.
     *
     * @return int<1, max>|null
     */
    public function getFirstItemNumber(): ?int;

    /**
     * Return the one-based position of the last item on this page.
     *
     * @return int<1, max>|null
     */
    public function getLastItemNumber(): ?int;

    /**
     * @param int<1, max> $page
     */
    public function getUrl(int $page): string;

    public function getFirstUrl(): string;

    /**
     * Return the final page URL, or null when the total is unknown.
     */
    public function getLastUrl(): ?string;

    public function getPages(): Navigation;

    public function isFirst(): bool;

    public function isLast(): bool;

    public function isOutOfRange(): bool;
}
