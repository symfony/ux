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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\UX\Router\RouteNormalizer;

final class RouteNormalizerTest extends TestCase
{
    public function testKeepsOnlyDefaultsUsedByTheGenerator(): void
    {
        $route = new Route('/blog/{page}', [
            'page' => 1,
            '_controller' => 'App\Controller\BlogController::list',
            '_locale' => 'fr',
            '_canonical_route' => 'blog',
            '_fragment' => 'top',
            'unrelated' => 'value',
        ]);

        self::assertSame([
            'page' => 1,
            '_locale' => 'fr',
            '_canonical_route' => 'blog',
            '_fragment' => 'top',
        ], (array) new RouteNormalizer()->normalize($route)['defaults']);
    }

    public function testKeepsTheQueryDefaultOnlyWhenTheUrlGeneratorUsesIt(): void
    {
        $route = new Route('/', ['_query' => ['sort' => 'asc']]);
        $routes = new RouteCollection();
        $routes->add('route', $route);
        $urlGeneratorUsesIt = '/?sort=asc' === new UrlGenerator($routes, new RequestContext())->generate('route');

        self::assertSame($urlGeneratorUsesIt, isset(new RouteNormalizer()->normalize($route)['defaults']->_query));
    }

    public function testEmptyDefaultsAreAnObject(): void
    {
        self::assertSame('{}', json_encode(new RouteNormalizer()->normalize(new Route('/'))['defaults']));
    }

    public function testConvertsRequirementsToJavaScript(): void
    {
        self::assertSame(
            [['variable', '/', '[^/]+', 'slug'], ['text', '/blog']],
            new RouteNormalizer()->normalize(new Route('/blog/{slug}'))['tokens'],
        );
    }

    public function testUnsupportedRequirementBecomesNull(): void
    {
        $tokens = new RouteNormalizer()->normalize(new Route('/{name}', [], ['name' => '\h+']))['tokens'];

        self::assertNull($tokens[0][2]);
    }

    public function testHostTokensAndSchemes(): void
    {
        $normalized = new RouteNormalizer()->normalize(new Route('/', [], [], [], '{sub}.example.com', ['HTTPS']));

        self::assertSame([['text', '.example.com'], ['variable', '', '[^\.]+', 'sub']], $normalized['hostTokens']);
        self::assertSame(['https'], $normalized['schemes']);
    }

    public function testKeepsUtf8AndImportantFlags(): void
    {
        $tokens = new RouteNormalizer()->normalize(new Route('/{!page}', ['page' => 1], [], ['utf8' => true]))['tokens'];

        self::assertSame(['variable', '/', '[^/]+', 'page', true, true], $tokens[0]);
    }
}
