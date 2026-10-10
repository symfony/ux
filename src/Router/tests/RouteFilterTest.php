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
use Symfony\UX\Router\RouteFilter;

final class RouteFilterTest extends TestCase
{
    /**
     * @param list<string>         $patterns
     * @param array<string, mixed> $options
     * @param array<string, mixed> $defaults
     */
    #[DataProvider('provideRoutes')]
    public function testIsExposed(bool $expected, array $patterns, string $name, array $options = [], array $defaults = []): void
    {
        self::assertSame($expected, new RouteFilter($patterns)->isExposed($name, new Route('/', $defaults, [], $options)));
    }

    public static function provideRoutes(): iterable
    {
        yield 'nothing is exposed by default' => [false, [], 'app_home'];
        yield 'matching pattern' => [true, ['app_*'], 'app_home'];
        yield 'non-matching pattern' => [false, ['app_*'], 'admin_home'];
        yield 'exact name' => [true, ['app_home'], 'app_home'];
        yield 'patterns are anchored' => [false, ['home'], 'app_home'];
        yield 'wildcard in the middle' => [true, ['app_*_show'], 'app_blog_show'];
        yield 'excluded pattern' => [false, ['app_*', '!app_admin_*'], 'app_admin_users'];
        yield 'exclusion alone exposes nothing' => [false, ['!app_admin_*'], 'app_home'];
        yield 'expose option without pattern' => [true, [], 'legacy_search', ['expose' => true]];
        yield 'expose option wins over an exclusion' => [true, ['app_*', '!app_admin_*'], 'app_admin_users', ['expose' => true]];
        yield 'disabled expose option wins over a pattern' => [false, ['app_*'], 'app_home', ['expose' => false]];
        yield 'non-boolean expose option is ignored' => [false, [], 'app_home', ['expose' => 'true']];
        yield 'regex characters are literal' => [false, ['app.home'], 'appXhome'];
        yield 'blank patterns are ignored' => [false, ['  ', ''], 'app_home'];
        yield 'star exposes everything' => [true, ['*'], '_profiler'];
        yield 'localized route by its canonical name' => [true, ['app_about'], 'app_about.en', [], ['_canonical_route' => 'app_about']];
        yield 'localized route by its variant name' => [true, ['app_about.en'], 'app_about.en', [], ['_canonical_route' => 'app_about']];
        yield 'localized route excluded by its canonical name' => [false, ['app_*', '!app_about'], 'app_about.fr', [], ['_canonical_route' => 'app_about']];
    }
}
