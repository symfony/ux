<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\UX\Router\TypeScriptRoutePrinter;

final class TypeScriptRoutePrinterTest extends TestCase
{
    #[DataProvider('provideRoutes')]
    public function testPrint(Route $route, string $expected): void
    {
        self::assertSame($expected, new TypeScriptRoutePrinter()->print($route));
    }

    public static function provideRoutes(): iterable
    {
        yield 'no variable' => [new Route('/'), 'Route'];
        yield 'required variable' => [new Route('/blog/{slug}'), 'Route<{ "slug": string | number }>'];
        yield 'variable with a default' => [new Route('/blog/{page}', ['page' => 1]), 'Route<{ "page"?: string | number }>'];
        yield 'regex requirement' => [new Route('/{id}', [], ['id' => '\d+']), 'Route<{ "id": string | number }>'];
        yield 'literal requirement' => [new Route('/{format}', [], ['format' => 'json|xml']), 'Route<{ "format": "json" | "xml" }>'];
        yield 'numeric literal requirement' => [new Route('/{v}', [], ['v' => '1|2']), 'Route<{ "v": "1" | 1 | "2" | 2 }>'];
        yield 'escaped literal requirement' => [new Route('/{l}', [], ['l' => 'en\-GB|fr']), 'Route<{ "l": "en-GB" | "fr" }>'];
        yield 'locale variable is always optional' => [new Route('/{_locale}/home', [], ['_locale' => 'en|fr']), 'Route<{ "_locale"?: "en" | "fr" }>'];
        yield 'host variable' => [new Route('/', [], [], [], '{sub}.example.com'), 'Route<{ "sub": string | number }>'];
        yield 'host and path variables' => [new Route('/{id}', [], [], [], '{sub}.example.com'), 'Route<{ "sub": string | number; "id": string | number }>'];
        yield 'localized route' => [new Route('/a-propos', ['_locale' => 'fr', '_canonical_route' => 'about'], ['_locale' => 'fr']), 'Route<{ "_locale"?: "fr" }, "about">'];
        yield 'localized route with a locale variable' => [new Route('/{_locale}/x', ['_locale' => 'fr', '_canonical_route' => 'x'], ['_locale' => 'fr']), 'Route<{ "_locale"?: "fr" }, "x">'];
        yield 'canonical route without locale' => [new Route('/x', ['_canonical_route' => 'x']), 'Route<Record<never, never>, "x">'];
    }
}
