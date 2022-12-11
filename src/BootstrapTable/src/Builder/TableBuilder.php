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

use Symfony\UX\BootstrapTable\Model\Table;

/**
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 */
class TableBuilder implements TableBuilderInterface
{
    public $data = [];
    public $columns = [];
    public $tableAttributes = [];
    public $columnsAttributes = [];

    public function createTable(): Table
    {
        return new Table($this->data, $this->columns, $this->tableAttributes, $this->columnsAttributes);
    }

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

    public function addTableAttributes(array $tableAttributes): TableBuilderInterface
    {
        $this->tableAttributes = $tableAttributes;

        return $this;
    }

    public function addColumnsAttributes(array $columnsAttributes): TableBuilderInterface
    {
        $this->columnsAttributes = $columnsAttributes;

        return $this;
    }
}
