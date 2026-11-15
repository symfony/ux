<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Column;

use Symfony\UX\DataTable\TypeInterface;

/**
 * @author Simon Daigre <simon@daig.re>
 */
interface ColumnTypeInterface extends TypeInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildHeaderView(ColumnView $view, Column $column, array $options): void;

    /**
     * @param array<string, mixed> $options
     */
    public function buildCellView(ColumnView $view, Column $column, mixed $row, array $options): void;
}
