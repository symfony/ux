<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Fixtures;

use Symfony\UX\DataTable\AbstractDataTableType;
use Symfony\UX\DataTable\Column\BooleanColumnType;
use Symfony\UX\DataTable\Column\EnumColumnType;
use Symfony\UX\DataTable\Column\NumberColumnType;
use Symfony\UX\DataTable\DataTableBuilder;
use Symfony\UX\DataTable\Filter\BooleanFilterType;
use Symfony\UX\DataTable\Filter\SearchFilterType;

final class ProductDataTableType extends AbstractDataTableType
{
    public function buildDataTable(DataTableBuilder $builder, array $options): void
    {
        $builder
            ->addColumn('name', options: ['sort' => true])
            ->addColumn('price', NumberColumnType::class, ['decimals' => 2, 'sort' => true])
            ->addColumn('active', BooleanColumnType::class)
            ->addColumn('status', EnumColumnType::class)
            ->addFilter('q', SearchFilterType::class, ['fields' => ['name']])
            ->addFilter('active', BooleanFilterType::class)
            ->setDefaultSort(['name' => \SortDirection::Ascending])
        ;
    }
}
