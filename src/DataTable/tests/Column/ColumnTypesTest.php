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

use PHPUnit\Framework\TestCase;
use Symfony\UX\DataTable\Column\BooleanColumnType;
use Symfony\UX\DataTable\Column\Column;
use Symfony\UX\DataTable\Column\ColumnTypeInterface;
use Symfony\UX\DataTable\Column\ColumnView;
use Symfony\UX\DataTable\Column\DateTimeColumnType;
use Symfony\UX\DataTable\Column\EnumColumnType;
use Symfony\UX\DataTable\Column\LinkColumnType;
use Symfony\UX\DataTable\Column\NumberColumnType;
use Symfony\UX\DataTable\Column\TemplateColumnType;
use Symfony\UX\DataTable\Tests\Fixtures\Size;
use Symfony\UX\DataTable\Tests\Fixtures\Status;
use Symfony\UX\DataTable\TypeRegistry;

final class ColumnTypesTest extends TestCase
{
    public function testNumber(): void
    {
        self::assertSame('1,234.50', self::cell(NumberColumnType::class, 1234.5, ['decimals' => 2])->vars['value']);
        self::assertSame('1 234,5', self::cell(NumberColumnType::class, '1234.5', ['decimals' => 1, 'decimal_separator' => ',', 'thousands_separator' => ' '])->vars['value']);
        self::assertSame('n/a', self::cell(NumberColumnType::class, 'n/a')->vars['value']);
        self::assertSame('12.99', self::cell(NumberColumnType::class, 12.99)->vars['value']);
        self::assertSame('1,234', self::cell(NumberColumnType::class, 1234)->vars['value']);
        self::assertSame('1,234.50', self::cell(NumberColumnType::class, '1234.50')->vars['value']);
        self::assertSame('0.3', self::cell(NumberColumnType::class, 0.1 + 0.2)->vars['value']);
        self::assertSame('0.00000015', self::cell(NumberColumnType::class, 1.5E-7)->vars['value']);
        self::assertSame('0.00000015', self::cell(NumberColumnType::class, '1.5E-7')->vars['value']);
        self::assertSame('2', self::cell(NumberColumnType::class, 2.0)->vars['value']);
    }

    public function testBoolean(): void
    {
        $view = self::cell(BooleanColumnType::class, true);
        self::assertSame('Yes', $view->vars['value']);
        self::assertSame('UXDataTableBundle', $view->vars['translation_domain']);
        self::assertFalse($view->vars['translatable']);
        self::assertSame('No', self::cell(BooleanColumnType::class, false)->vars['value']);

        $view = self::cell(BooleanColumnType::class, 0, ['false_label' => 'Off']);
        self::assertSame('Off', $view->vars['value']);
        self::assertSame('admin', $view->vars['translation_domain']);

        self::assertTrue(self::cell(BooleanColumnType::class, true, ['true_label' => Size::Large])->vars['translatable']);
        self::assertNull(self::cell(BooleanColumnType::class, null)->vars['value']);
    }

    public function testDateTime(): void
    {
        $date = new \DateTimeImmutable('2026-10-29 12:30:00', new \DateTimeZone('UTC'));

        self::assertSame('2026-10-29 12:30:00', self::cell(DateTimeColumnType::class, $date)->vars['value']);
        self::assertSame('29/10/2026 13:30', self::cell(DateTimeColumnType::class, $date, ['format' => 'd/m/Y H:i', 'timezone' => 'Europe/Paris'])->vars['value']);
        self::assertNull(self::cell(DateTimeColumnType::class, null)->vars['value']);
    }

    public function testEnum(): void
    {
        $view = self::cell(EnumColumnType::class, Status::Published);
        self::assertSame('Published', $view->vars['value']);
        self::assertFalse($view->vars['translatable']);

        $view = self::cell(EnumColumnType::class, Size::Large);
        self::assertSame(Size::Large, $view->vars['value']);
        self::assertTrue($view->vars['translatable']);
        self::assertNull(self::cell(EnumColumnType::class, null)->vars['value']);
    }

    public function testLink(): void
    {
        $view = self::cell(LinkColumnType::class, 'Chair', ['href' => '/chair', 'link_attr' => ['target' => '_blank']]);
        self::assertSame('/chair', $view->vars['href']);
        self::assertSame(['target' => '_blank'], $view->vars['link_attr']);

        $view = self::cell(LinkColumnType::class, 'Chair', ['href' => static fn (string $value, array $row): string => '/products/'.$row['id']]);
        self::assertSame('/products/7', $view->vars['href']);
        self::assertSame([], $view->vars['link_attr']);

        self::assertSame('https://symfony.com', self::cell(LinkColumnType::class, 'https://symfony.com')->vars['href']);
        self::assertSame('mailto:contact@symfony.com', self::cell(LinkColumnType::class, ' mailto:contact@symfony.com')->vars['href']);
        self::assertNull(self::cell(LinkColumnType::class, null)->vars['href']);
        self::assertNull(self::cell(LinkColumnType::class, 'Chair')->vars['href']);
        self::assertNull(self::cell(LinkColumnType::class, ' JavaScript:alert(1)')->vars['href']);
        self::assertNull(self::cell(LinkColumnType::class, 'data:text/html,<script>alert(1)</script>')->vars['href']);
    }

    public function testTemplate(): void
    {
        $view = self::cell(TemplateColumnType::class, 'Chair', ['template' => 'cell.html.twig', 'template_vars' => ['size' => 'sm']]);
        self::assertSame('cell.html.twig', $view->vars['template']);
        self::assertSame(['size' => 'sm'], $view->vars['template_vars']);

        $view = self::cell(TemplateColumnType::class, 'Chair', ['template' => 'cell.html.twig', 'template_vars' => static fn (string $value, array $row): array => ['id' => $row['id'], 'value' => $value]]);
        self::assertSame(['id' => 7, 'value' => 'Chair'], $view->vars['template_vars']);
    }

    /**
     * @param class-string<ColumnTypeInterface> $type
     * @param array<string, mixed>              $options
     */
    private static function cell(string $type, mixed $value, array $options = []): ColumnView
    {
        [$types, $options] = new TypeRegistry()->resolve($type, ColumnTypeInterface::class, $options);

        return new Column('value', $types, $options)->createCellView(['id' => 7, 'value' => $value], ['translation_domain' => 'admin']);
    }
}
