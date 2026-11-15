<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable;

use Symfony\Component\Form\FormView;
use Symfony\UX\DataTable\Action\ActionView;
use Symfony\UX\DataTable\Column\ColumnView;
use Symfony\UX\Pagination\Pagination;

/**
 * @author Simon Daigre <simon@daig.re>
 */
final class DataTableView
{
    /**
     * @var array<string, mixed>
     */
    public array $vars = [];

    /**
     * @var array<string, ColumnView>
     */
    public array $headers = [];

    /**
     * @var list<RowView>
     */
    public array $rows = [];

    /**
     * @var array<string, ActionView> the visible global actions
     */
    public array $actions = [];

    /**
     * @var array<string, ActionView> the visible batch actions
     */
    public array $batchActions = [];

    /**
     * @param Pagination<mixed> $pagination
     */
    public function __construct(
        public readonly Pagination $pagination,
        public readonly ?FormView $filterForm = null,
    ) {
    }
}
