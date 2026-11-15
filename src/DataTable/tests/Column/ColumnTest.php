<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Column;

use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\UX\DataTable\Column\Column;
use Symfony\UX\DataTable\Column\ColumnType;
use Symfony\UX\DataTable\Column\ColumnTypeInterface;
use Symfony\UX\DataTable\Column\NumberColumnType;
use Symfony\UX\DataTable\Tests\Fixtures\Entity\Category;
use Symfony\UX\DataTable\Tests\Fixtures\Entity\Product;
use Symfony\UX\DataTable\TypeRegistry;

final class ColumnTest extends TestCase
{
    public function testReadsTheValueAtTheColumnName(): void
    {
        self::assertSame('Chair', self::column('name')->getValue(new Product('Chair')));
        self::assertSame('Chair', self::column('name')->getValue(['name' => 'Chair']));
    }

    public function testReadsTheValueAtThePropertyPath(): void
    {
        self::assertSame('Furniture', self::column('category', ['property_path' => 'category.name'])->getValue(new Product('Chair', category: new Category('Furniture'))));
    }

    public function testReadsTheValueWithTheGetter(): void
    {
        self::assertSame('CHAIR', self::column('name', ['getter' => static fn (Product $product) => strtoupper($product->name)])->getValue(new Product('Chair')));
    }

    public function testHasNoValueWithoutPropertyPathOrRow(): void
    {
        self::assertNull(self::column('name', ['property_path' => false])->getValue(new Product('Chair')));
        self::assertNull(self::column('name')->getValue('Chair'));
    }

    public function testSorting(): void
    {
        self::assertFalse(self::column('name')->isSortable());
        self::assertTrue(self::column('name', ['sort' => true])->isSortable());
        self::assertSame('name', self::column('name', ['sort' => true])->getSortPath());
        self::assertSame('category.name', self::column('category', ['sort' => true, 'property_path' => 'category?.name'])->getSortPath());
        self::assertSame('category.position', self::column('category', ['sort' => 'category.position'])->getSortPath());
    }

    public function testCreatesTheHeaderView(): void
    {
        $view = self::column('createdAt', ['header_attr' => ['class' => 'date']])->createHeaderView(['sort_url' => '?sort']);

        self::assertSame('createdAt', $view->vars['name']);
        self::assertSame('Created at', $view->vars['label']);
        self::assertSame(['class' => 'date'], $view->vars['attr']);
        self::assertSame(['column'], $view->vars['block_prefixes']);
        self::assertSame('?sort', $view->vars['sort_url']);
        self::assertSame('Category name', self::column('category.name')->createHeaderView()->vars['label']);
        self::assertSame('Category', self::column('category.name', ['label' => 'Category'])->createHeaderView()->vars['label']);
        self::assertFalse(self::column('name', ['label' => false])->createHeaderView()->vars['label']);
    }

    public function testCreatesTheCellView(): void
    {
        $product = new Product('Chair', 12.5);
        $view = self::column('price', ['attr' => ['class' => 'number']], NumberColumnType::class)->createCellView($product, ['translation_domain' => 'admin']);

        self::assertSame('price', $view->vars['name']);
        self::assertSame($product, $view->vars['row']);
        self::assertSame('12.5', $view->vars['value']);
        self::assertSame(['class' => 'number'], $view->vars['attr']);
        self::assertSame(['column', 'number_column'], $view->vars['block_prefixes']);
        self::assertSame('admin', $view->vars['translation_domain']);
    }

    public function testGetters(): void
    {
        $column = self::column('name', ['sort' => true]);

        self::assertSame('name', $column->getName());
        self::assertTrue($column->getOptions()['sort']);
    }

    #[TestWith(['label'])]
    #[TestWith(['property_path'])]
    public function testRejectsTrueAsLabelOrPropertyPath(string $option): void
    {
        $this->expectException(InvalidOptionsException::class);

        self::column('name', [$option => true]);
    }

    /**
     * @param array<string, mixed>              $options
     * @param class-string<ColumnTypeInterface> $type
     */
    private static function column(string $name, array $options = [], string $type = ColumnType::class): Column
    {
        [$types, $options] = new TypeRegistry()->resolve($type, ColumnTypeInterface::class, $options);

        return new Column($name, $types, $options);
    }
}
