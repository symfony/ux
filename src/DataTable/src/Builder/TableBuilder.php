<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Builder;

use Symfony\UX\DataTable\Model\Table;

/**
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 */
class TableBuilder implements TableBuilderInterface
{
    public $data = [];
    public $columns = [];
    public $options = [];

    public function createTable(): Table
    {
        return new Table($this->data, $this->columns, $this->options);
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

    public function addOptions(array $options): TableBuilderInterface
    {
        $this->options = $options;

        return $this;
    }
}
