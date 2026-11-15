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
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\UX\DataTable\Column\ColumnType;
use Symfony\UX\DataTable\Column\ColumnTypeInterface;
use Symfony\UX\DataTable\Column\NumberColumnType;
use Symfony\UX\DataTable\DataTableType;
use Symfony\UX\DataTable\DataTableTypeInterface;
use Symfony\UX\DataTable\Exception\InvalidArgumentException;
use Symfony\UX\DataTable\TypeRegistry;

final class TypeRegistryTest extends TestCase
{
    public function testResolvesTheTypeChainAndItsOptions(): void
    {
        [$types, $options] = new TypeRegistry()->resolve(NumberColumnType::class, ColumnTypeInterface::class, ['decimals' => 2]);

        self::assertCount(2, $types);
        self::assertInstanceOf(ColumnType::class, $types[0]);
        self::assertInstanceOf(NumberColumnType::class, $types[1]);
        self::assertSame(2, $options['decimals']);
        self::assertFalse($options['sort']);
    }

    public function testReusesTypeInstances(): void
    {
        $registry = new TypeRegistry();

        self::assertSame(
            $registry->resolve(NumberColumnType::class, ColumnTypeInterface::class)[0][1],
            $registry->resolve(NumberColumnType::class, ColumnTypeInterface::class)[0][1],
        );
    }

    public function testFetchesTypesFromTheLocator(): void
    {
        $type = new DataTableType('custom.html.twig');
        $registry = new TypeRegistry(new ServiceLocator([DataTableType::class => static fn () => $type]));

        [$types, $options] = $registry->resolve(DataTableType::class, DataTableTypeInterface::class);

        self::assertSame([$type], $types);
        self::assertSame('custom.html.twig', $options['theme']);
    }

    public function testRejectsAClassOfAnotherKind(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf('The type "%s" must implement "%s".', ColumnType::class, DataTableTypeInterface::class));

        new TypeRegistry()->resolve(ColumnType::class, DataTableTypeInterface::class);
    }

    public function testRejectsUndefinedOptions(): void
    {
        $this->expectException(UndefinedOptionsException::class);

        new TypeRegistry()->resolve(ColumnType::class, ColumnTypeInterface::class, ['decimals' => 2]);
    }
}
