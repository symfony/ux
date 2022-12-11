<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\BootstrapTable\Model;

/**
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 *
 * @final
 */
class Table
{
    private $data = [];
    private $columns = [];
    private $tableAttributes = [];
    private $columnsAttributes = [];

    public function __construct($data = [], $columns = [], $tableAttributes = [], $columnsAttributes = [])
    {
        $this->data = $data;
        $this->columns = $columns;
        $this->tableAttributes = $tableAttributes;
        $this->columnsAttributes = $columnsAttributes;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function setColumns(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getTableAttributes(): array
    {
        return $this->tableAttributes;
    }

    public function setTableAttributes(array $tableAttributes): void
    {
        $this->tableAttributes = $tableAttributes;
    }

    public function getColumnsAttributes(): array
    {
        return $this->columnsAttributes;
    }

    public function setColumnsAttributes(array $columnsAttributes): void
    {
        $this->columnsAttributes = $columnsAttributes;
    }
}
