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
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\UX\DataTable\PropertyReader;
use Symfony\UX\DataTable\Tests\Fixtures\Entity\Category;
use Symfony\UX\DataTable\Tests\Fixtures\Entity\Product;

final class PropertyReaderTest extends TestCase
{
    public function testReadsObjects(): void
    {
        $reader = new PropertyReader();
        $product = new Product('Chair', category: new Category('Furniture'));

        self::assertSame('Chair', $reader->read($product, 'name'));
        self::assertSame('Furniture', $reader->read($product, 'category.name'));
    }

    public function testReadsArraysWithDottedPaths(): void
    {
        $reader = new PropertyReader();
        $row = ['name' => 'Chair', 'category' => ['name' => 'Furniture']];

        self::assertSame('Chair', $reader->read($row, 'name'));
        self::assertSame('Furniture', $reader->read($row, 'category.name'));
        self::assertSame('Furniture', $reader->read($row, '[category][name]'));
        self::assertNull($reader->read($row, 'missing'));
    }

    public function testReadsObjectsInArraysAndArraysInObjects(): void
    {
        $reader = new PropertyReader();

        self::assertSame('Furniture', $reader->read(['category' => new Category('Furniture')], 'category.name'));
        self::assertSame('Chair', $reader->read([0 => new Product('Chair'), 'count' => 3], '0.name'));
        self::assertSame('Home', $reader->read(new \ArrayObject(['category' => new Category('Furniture', new Category('Home'))]), 'category.parent.name'));
    }

    public function testReadsNullAlongThePathAsNull(): void
    {
        $reader = new PropertyReader();

        self::assertNull($reader->read(new Product('Chair'), 'category.name'));
        self::assertNull($reader->read(new Product('Chair'), 'category?.name'));
        self::assertNull($reader->read(['name' => 'Chair'], 'name.length'));
    }

    public function testRejectsUnknownProperties(): void
    {
        $this->expectException(NoSuchPropertyException::class);

        new PropertyReader()->read(new Product('Chair'), 'color');
    }
}
