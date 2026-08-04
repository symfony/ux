<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Pagination\CursorPagination;
use Symfony\UX\Pagination\CursorPaginationBuilder;
use Symfony\UX\Pagination\CursorPaginationInterface;
use Symfony\UX\Pagination\NumberedPaginationInterface;
use Symfony\UX\Pagination\Pagination;
use Symfony\UX\Pagination\PaginationBuilder;
use Symfony\UX\Pagination\PaginationInterface;
use Symfony\UX\Pagination\PaginatorInterface;

#[CoversNothing]
final class PaginatorInterfaceTest extends TestCase
{
    public function testPublicMethodsExposeTheSimpleAndBuilderContracts()
    {
        self::assertSame(
            NumberedPaginationInterface::class,
            self::getReturnType('paginate'),
        );
        self::assertSame(
            PaginationBuilder::class,
            self::getReturnType('query'),
        );
        self::assertSame(
            PaginationBuilder::class,
            self::getReturnType('fromCallbacks'),
        );
        self::assertSame(CursorPaginationBuilder::class, self::getReturnType('cursor'));

        self::assertNotContains('paginateCallable', get_class_methods(PaginatorInterface::class));
        self::assertNotContains('cursorPaginate', get_class_methods(PaginatorInterface::class));
    }

    public function testCommonResultContractDoesNotExposeConfigurationOrTransformation()
    {
        $methods = get_class_methods(PaginationInterface::class);

        self::assertNotContains('map', $methods);
        self::assertTrue(method_exists(Pagination::class, 'map'));
        self::assertTrue(method_exists(CursorPagination::class, 'map'));

        foreach ([
            'queryParameters',
            'preserveQueryString',
            'discardQueryString',
            'excludeQueryParameters',
            'fragment',
            'path',
        ] as $method) {
            self::assertNotContains($method, $methods);
            self::assertFalse(method_exists(Pagination::class, $method));
            self::assertFalse(method_exists(CursorPagination::class, $method));
        }
    }

    public function testNumberedResultContractDoesNotExposeFlowPolicies()
    {
        $methods = get_class_methods(NumberedPaginationInterface::class);

        self::assertNotContains('throwOnOutOfRange', $methods);
        self::assertNotContains('throwOnCanonicalPage', $methods);
        self::assertContains('throwOnOutOfRange', get_class_methods(Pagination::class));
        self::assertNotContains('throwOnCanonicalPage', get_class_methods(Pagination::class));
    }

    public function testSerializationShapesStayOutOfTheResultInterfaces()
    {
        $numbered = get_class_methods(NumberedPaginationInterface::class);
        foreach (['getMetadata', 'getLinks', 'getAbsoluteUrl'] as $method) {
            self::assertNotContains($method, $numbered);
            self::assertTrue(method_exists(Pagination::class, $method));
        }

        self::assertNotContains('getLinks', get_class_methods(CursorPaginationInterface::class));
        self::assertTrue(method_exists(CursorPagination::class, 'getLinks'));
    }

    private static function getReturnType(string $method): string
    {
        $type = new \ReflectionMethod(PaginatorInterface::class, $method)->getReturnType();
        self::assertInstanceOf(\ReflectionNamedType::class, $type);

        return $type->getName();
    }
}
