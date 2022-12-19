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
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 */
interface TableBuilderInterface
{
    public function createTable(): Table;

    public function addData(array $data): TableBuilderInterface;

    public function addColumns(array $columns): TableBuilderInterface;

    public function addOptions(array $options): TableBuilderInterface;
}
