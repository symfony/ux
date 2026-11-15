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

/**
 * @author Simon Daigre <simon@daig.re>
 */
interface DataTableTypeInterface extends TypeInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildDataTable(DataTableBuilder $builder, array $options): void;

    /**
     * @param array<string, mixed> $options
     */
    public function buildView(DataTableView $view, DataTable $dataTable, array $options): void;
}
