<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\DataTable\Column\AbstractColumnType;
use Symfony\UX\DataTable\Column\Column;
use Symfony\UX\DataTable\Column\ColumnType;
use Symfony\UX\DataTable\Column\ColumnTypeInterface;
use Symfony\UX\DataTable\Column\NumberColumnType;
use Symfony\UX\DataTable\DataTableType;
use Symfony\UX\DataTable\DataTableTypeInterface;
use Symfony\UX\DataTable\Filter\AbstractFilterType;
use Symfony\UX\DataTable\Filter\FilterType;
use Symfony\UX\DataTable\Filter\FilterTypeInterface;
use Symfony\UX\DataTable\Tests\Fixtures\ProductDataTableType;
use Symfony\UX\DataTable\TypeRegistry;

final class AbstractTypesTest extends TestCase
{
    public function testColumnTypesInheritFromTheRootColumnType(): void
    {
        $type = new class extends AbstractColumnType {
        };
        [$types, $options] = new TypeRegistry()->resolve($type::class, ColumnTypeInterface::class);

        self::assertSame(ColumnType::class, $type->getParent());
        self::assertInstanceOf($type::class, $types[1]);
        self::assertSame(1, new Column('value', $types, $options)->createCellView(['value' => 1])->vars['value']);
        self::assertSame('number_column', new NumberColumnType()->getBlockPrefix());
    }

    public function testFilterTypesInheritFromTheRootFilterType(): void
    {
        $type = new class extends AbstractFilterType {
        };

        self::assertSame(FilterType::class, $type->getParent());
        self::assertArrayHasKey('operator', new TypeRegistry()->resolve($type::class, FilterTypeInterface::class)[1]);
    }

    public function testDataTableTypesInheritFromTheRootDataTableType(): void
    {
        self::assertSame(DataTableType::class, new ProductDataTableType()->getParent());
        self::assertSame('product_data_table', new ProductDataTableType()->getBlockPrefix());
        self::assertArrayHasKey('theme', new TypeRegistry()->resolve(ProductDataTableType::class, DataTableTypeInterface::class)[1]);
    }
}
