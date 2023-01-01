<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\BootstrapTable\Builder;

use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\BootstrapTable\Model\Table;

/**
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 */
class TableBuilder implements TableBuilderInterface
{
    public const SORT_ASC = 'ASC';
    public const SORT_DESC = 'DESC';

    private $data = [];
    private $columns = [];
    private $options = [];
    private $sort;
    private $search = '';
    private $pagination = false;
    private $itemsPerPage = 5;
    private $currentPage = 0;
    private $request;

    public function addData(array $data): TableBuilderInterface
    {
        $this->data = $data;

        return $this;
    }

    public function addColumns(array $columns): TableBuilderInterface
    {
        $this->columns = $columns;

        return $this;
    }

    public function addOptions(array $options): TableBuilderInterface
    {
        $this->options = $options;

        return $this;
    }

    public function setSort(string $field, string $direction): TableBuilderInterface
    {
        if (self::SORT_ASC !== $direction && self::SORT_DESC !== $direction) {
            throw new \RuntimeException(sprintf('the direction should be equal to %s or %s', self::SORT_ASC, self::SORT_DESC));
        }

        $this->sort = [$field, $direction];

        return $this;
    }

    public function setSearch(string $search): TableBuilderInterface
    {
        $this->search = $search;

        return $this;
    }

    public function enablePagination(): TableBuilderInterface
    {
        $this->pagination = true;

        return $this;
    }

    public function setItemPerPage(int $itemsPerPage): TableBuilderInterface
    {
        $this->itemsPerPage = $itemsPerPage;

        return $this;
    }

    public function setCurrentPage(int $currentPage): TableBuilderInterface
    {
        $this->currentPage = $currentPage;

        return $this;
    }

    public function handleRequest(Request $request): TableBuilderInterface
    {
        $this->request = $request;

        return $this;
    }

    public function createTable(): Table
    {
        $this->init();

        $data = $this->data;

        if ('' !== $this->search) {
            foreach ($data as $key => $row) {
                if (!\in_array($this->search, $row, false)) {
                    unset($data[$key]);
                }
            }
            $data = array_values($data);
        }

        if (null !== $this->sort) {
            usort($data, function ($a, $b) {
                return $this->sortItems($a, $b);
            });
        }

        if ($this->pagination) {
            $data = \array_slice($data, $this->currentPage * $this->itemsPerPage, $this->itemsPerPage);
        }

        return new Table($data, $this->columns, $this->options);
    }

    private function init(): void
    {
        /** @var Request $request */
        $request = $this->request;

        if (null === $request) {
            return;
        }

        $page = $request->get('page');

        if (null !== $page) {
            $this->currentPage = (int) $page;
        }

        $search = $request->get('search');

        if (null !== $search) {
            $this->setSearch($search);
        }

        $sort = $request->get('sort');

        if (null !== $sort) {
            $this->sort = explode(',', $sort, 2);
        }
    }

    private function sortItems($a, $b): bool
    {
        [$field, $direction] = $this->sort;

        if (self::SORT_DESC === $direction) {
            return $a[$field] < $b[$field];
        }

        return $a[$field] > $b[$field];
    }
}
