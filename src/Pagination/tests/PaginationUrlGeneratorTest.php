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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Pagination\Navigation\PaginationUrlGenerator;

#[CoversClass(PaginationUrlGenerator::class)]
final class PaginationUrlGeneratorTest extends TestCase
{
    public function testParameterNamesCanBeConfiguredImmutably()
    {
        $generator = new PaginationUrlGenerator(basePath: '/items');
        $configured = $generator
            ->withQueryParameter('p')
            ->withCursorParameter('after');

        self::assertSame('page', $generator->getQueryParameterName());
        self::assertSame('cursor', $generator->getCursorParameterName());
        self::assertSame('p', $configured->getQueryParameterName());
        self::assertSame('after', $configured->getCursorParameterName());
        self::assertSame('/items?p=2', $configured->getUrl(2));
        self::assertSame('/items?after=opaque', $configured->getCursorUrl('opaque'));
    }

    public function testEmptyParameterAndRouteNamesAreRejected()
    {
        $generator = new PaginationUrlGenerator();

        foreach ([
            static fn () => $generator->withQueryParameter(''),
            static fn () => $generator->withCursorParameter(''),
            static fn () => $generator->withRoute(''),
        ] as $configure) {
            try {
                $configure();
                self::fail('Empty names must be rejected.');
            } catch (\InvalidArgumentException $exception) {
                self::assertStringContainsString('must not be empty', $exception->getMessage());
            }
        }
    }

    public function testEmptyExcludedQueryParameterIsRejected()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        new PaginationUrlGenerator()->withoutQueryParameters('');
    }

    public function testWithRouteReplacesPathConfiguration()
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('catalog', ['category' => 'books', 'page' => 2], UrlGeneratorInterface::ABSOLUTE_PATH)
            ->willReturn('/catalog/books?page=2');

        $url = new PaginationUrlGenerator(basePath: '/old', urlGenerator: $urlGenerator)
            ->withRoute('catalog', ['category' => 'books'])
            ->getUrl(2);

        self::assertSame('/catalog/books?page=2', $url);
    }

    public function testUrlWithBasePath()
    {
        $recipe = new PaginationUrlGenerator(basePath: '/items');

        self::assertSame('/items', $recipe->getUrl(1));
        self::assertSame('/items?page=2', $recipe->getUrl(2));
        self::assertSame('/items?page=10', $recipe->getUrl(10));
    }

    public function testPageUrlsRejectNonPositivePages()
    {
        $generator = new PaginationUrlGenerator(basePath: '/items');

        foreach ([
            static fn () => $generator->getUrl(0),
            static fn () => $generator->getAbsoluteUrl(-1),
        ] as $generate) {
            try {
                $generate();
                self::fail('Non-positive pages must be rejected.');
            } catch (\InvalidArgumentException $exception) {
                self::assertStringContainsString('greater than or equal to 1', $exception->getMessage());
            }
        }
    }

    public function testUrlOmitsPageParamForFirstPage()
    {
        $recipe = new PaginationUrlGenerator(basePath: '/items');

        $url = $recipe->getUrl(1);

        self::assertStringNotContainsString('page=', $url);
    }

    public function testWithQueryParameters()
    {
        $recipe = new PaginationUrlGenerator(basePath: '/items');
        $withQueryParameters = $recipe->withQueryParameters(['sort' => 'name', 'filter' => 'active']);

        $url = $withQueryParameters->getUrl(2);

        self::assertStringContainsString('sort=name', $url);
        self::assertStringContainsString('filter=active', $url);
        self::assertStringContainsString('page=2', $url);
    }

    public function testWithQueryParametersImmutability()
    {
        $recipe = new PaginationUrlGenerator(basePath: '/items');
        $withQueryParameters = $recipe->withQueryParameters(['sort' => 'name']);

        self::assertStringNotContainsString('sort=', $recipe->getUrl(2));
        self::assertStringContainsString('sort=name', $withQueryParameters->getUrl(2));
    }

    public function testWithFragment()
    {
        $recipe = new PaginationUrlGenerator(basePath: '/items');
        $withFragment = $recipe->withFragment('results');

        $url = $withFragment->getUrl(2);

        self::assertStringContainsString('#results', $url);
    }

    public function testWithFragmentImmutability()
    {
        $recipe = new PaginationUrlGenerator(basePath: '/items');
        $withFragment = $recipe->withFragment('results');

        self::assertStringNotContainsString('#', $recipe->getUrl(2));
        self::assertStringContainsString('#results', $withFragment->getUrl(2));
    }

    public function testWithPath()
    {
        $recipe = new PaginationUrlGenerator(basePath: '/old-path');
        $withPath = $recipe->withPath('/new-path');

        self::assertStringContainsString('/new-path', $withPath->getUrl(2));
    }

    public function testWithQueryString()
    {
        $request = new Request(['existing' => 'param']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $recipe = new PaginationUrlGenerator(
            basePath: '/items',
            requestStack: $requestStack,
        );
        $withQueryString = $recipe->withQueryString();

        $url = $withQueryString->getUrl(2);

        self::assertStringContainsString('existing=param', $url);
        self::assertStringContainsString('page=2', $url);
    }

    public function testPreservesQueryStringByDefault()
    {
        $request = new Request([
            'q' => 'phone',
            'sort' => 'price',
            'filter' => ['color' => ['red']],
            'page' => '2',
        ]);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $url = new PaginationUrlGenerator(basePath: '/items', requestStack: $requestStack)->getUrl(3);

        parse_str((string) parse_url($url, \PHP_URL_QUERY), $query);
        self::assertSame([
            'q' => 'phone',
            'sort' => 'price',
            'filter' => ['color' => ['red']],
            'page' => '3',
        ], $query);
    }

    public function testOffsetUrlDropsAnExistingCursorParameter()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['cursor' => 'old', 'q' => 'phone']));

        $url = new PaginationUrlGenerator(basePath: '/items', requestStack: $requestStack)->getUrl(2);

        self::assertSame('/items?q=phone&page=2', $url);
    }

    public function testWithoutQueryStringDiscardsRequestParameters()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['q' => 'phone']));

        $url = new PaginationUrlGenerator(basePath: '/items', requestStack: $requestStack)
            ->withoutQueryString()
            ->withQueryParameters(['sort' => 'name'])
            ->getUrl(2);

        self::assertSame('/items?sort=name&page=2', $url);
    }

    public function testWithoutQueryParametersRemovesSelectedParametersBeforeExplicitParameters()
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request(['debug' => '1', 'token' => 'secret', 'sort' => 'price']));

        $url = new PaginationUrlGenerator(basePath: '/items', requestStack: $requestStack)
            ->withoutQueryParameters('debug', 'token')
            ->withQueryParameters(['sort' => 'name'])
            ->getUrl(2);

        self::assertSame('/items?sort=name&page=2', $url);
    }

    public function testWithQueryStringExcludesPageParam()
    {
        $request = new Request(['page' => '5', 'sort' => 'name']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $recipe = new PaginationUrlGenerator(
            basePath: '/items',
            requestStack: $requestStack,
        );
        $withQueryString = $recipe->withQueryString();

        $url = $withQueryString->getUrl(2);

        self::assertStringContainsString('sort=name', $url);
        self::assertStringContainsString('page=2', $url);
        self::assertStringNotContainsString('page=5', $url);
    }

    public function testCursorUrl()
    {
        $recipe = new PaginationUrlGenerator(basePath: '/items');

        $url = $recipe->getCursorUrl('abc123');

        self::assertStringContainsString('cursor=abc123', $url);
        self::assertStringNotContainsString('page=', $url);
    }

    public function testCursorUrlRejectsAnEmptyCursor()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cursor value must not be empty');

        new PaginationUrlGenerator(basePath: '/items')->getCursorUrl('');
    }

    public function testCursorUrlWithFragment()
    {
        $recipe = new PaginationUrlGenerator(basePath: '/items');
        $withFragment = $recipe->withFragment('results');

        $url = $withFragment->getCursorUrl('abc123');

        self::assertStringContainsString('cursor=abc123', $url);
        self::assertStringContainsString('#results', $url);
    }

    public function testCustomQueryParam()
    {
        $recipe = new PaginationUrlGenerator(
            queryParam: 'p',
            basePath: '/items',
        );

        self::assertSame('/items?p=2', $recipe->getUrl(2));
        self::assertStringNotContainsString('page=', $recipe->getUrl(2));
    }

    public function testUrlWithRouteAndGenerator()
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('app_items', ['page' => 2, 'category' => 'books'])
            ->willReturn('/items/books?page=2');

        $recipe = new PaginationUrlGenerator(
            route: 'app_items',
            routeParams: ['category' => 'books'],
            urlGenerator: $urlGenerator,
        );

        $url = $recipe->getUrl(2);

        self::assertSame('/items/books?page=2', $url);
    }

    public function testUrlWithRouteOmitsPageOneFromParams()
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('app_items', ['category' => 'books'])
            ->willReturn('/items/books');

        $recipe = new PaginationUrlGenerator(
            route: 'app_items',
            routeParams: ['category' => 'books'],
            urlGenerator: $urlGenerator,
        );

        $url = $recipe->getUrl(1);

        self::assertSame('/items/books', $url);
    }

    public function testChainedModifiers()
    {
        $recipe = new PaginationUrlGenerator(basePath: '/items');

        $modified = $recipe
            ->withQueryParameters(['sort' => 'date'])
            ->withFragment('list');

        $url = $modified->getUrl(3);

        self::assertStringContainsString('sort=date', $url);
        self::assertStringContainsString('page=3', $url);
        self::assertStringContainsString('#list', $url);
    }

    public function testUrlWithNoRequestStackReturnsEmptyPath()
    {
        $recipe = new PaginationUrlGenerator();

        $url = $recipe->getUrl(2);

        self::assertSame('?page=2', $url);
    }

    public function testUrlGeneratorWithoutRequestFallsBackToQueryString()
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);

        self::assertSame('?page=2', new PaginationUrlGenerator(urlGenerator: $urlGenerator)->getUrl(2));
    }

    public function testRequestWithoutRouteNameFallsBackToPath()
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/items'));
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);

        self::assertSame('/items?page=2', new PaginationUrlGenerator(
            requestStack: $requestStack,
            urlGenerator: $urlGenerator,
        )->getUrl(2));
    }

    public function testExposesTheConfiguredRouteName()
    {
        $recipe = new PaginationUrlGenerator();

        self::assertNull($recipe->getRouteName());
        self::assertSame('items_list', $recipe->withRoute('items_list')->getRouteName());
    }

    public function testAutoDetectedRouteReceivesMergedParams()
    {
        $request = new Request();
        $request->attributes->set('_route', 'app_items');
        $request->attributes->set('_route_params', ['category' => 'books']);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('app_items', ['category' => 'books', 'page' => 2])
            ->willReturn('/items/books?page=2');

        $recipe = new PaginationUrlGenerator(
            requestStack: $requestStack,
            urlGenerator: $urlGenerator,
        );

        $url = $recipe->getUrl(2);

        self::assertSame('/items/books?page=2', $url);
    }

    public function testPreservedQueryParametersCannotOverrideRouteParameters()
    {
        $request = Request::create('/articles/php?slug=spoofed&filter=recent');
        $request->attributes->set('_route', 'article_show');
        $request->attributes->set('_route_params', ['slug' => 'php']);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('article_show', ['slug' => 'php', 'filter' => 'recent', 'page' => 2])
            ->willReturn('/articles/php?filter=recent&page=2');

        $recipe = new PaginationUrlGenerator(
            requestStack: $requestStack,
            urlGenerator: $urlGenerator,
        );

        self::assertSame('/articles/php?filter=recent&page=2', $recipe->getUrl(2));
    }

    public function testGetCurrentPathFallsBackToPathInfo()
    {
        $request = Request::create('/my-path');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $recipe = new PaginationUrlGenerator(
            requestStack: $requestStack,
        );

        $url = $recipe->getUrl(2);

        self::assertStringContainsString('/my-path', $url);
        self::assertStringContainsString('page=2', $url);
    }

    public function testCursorUrlWithRoute()
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('app_items', ['cursor' => 'abc123'])
            ->willReturn('/items?cursor=abc123');

        $recipe = new PaginationUrlGenerator(
            route: 'app_items',
            urlGenerator: $urlGenerator,
        );

        $url = $recipe->getCursorUrl('abc123');

        self::assertSame('/items?cursor=abc123', $url);
    }

    public function testCursorUrlWithRouteAndFragment()
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('app_items', ['cursor' => 'abc123'])
            ->willReturn('/items?cursor=abc123');

        $recipe = new PaginationUrlGenerator(
            route: 'app_items',
            urlGenerator: $urlGenerator,
        );

        $withFragment = $recipe->withFragment('results');
        $url = $withFragment->getCursorUrl('abc123');

        self::assertSame('/items?cursor=abc123#results', $url);
    }

    public function testCursorUrlWithQueryString()
    {
        $request = new Request(['sort' => 'name']);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $recipe = new PaginationUrlGenerator(
            basePath: '/items',
            requestStack: $requestStack,
        );

        $withQs = $recipe->withQueryString();
        $url = $withQs->getCursorUrl('abc123');

        self::assertStringContainsString('sort=name', $url);
        self::assertStringContainsString('cursor=abc123', $url);
    }

    public function testWithQueryStringNoRequest()
    {
        $recipe = new PaginationUrlGenerator(basePath: '/items');
        $withQs = $recipe->withQueryString();

        $url = $withQs->getUrl(2);

        self::assertSame('/items?page=2', $url);
    }

    public function testCursorUrlWithNoPath()
    {
        $recipe = new PaginationUrlGenerator();

        $url = $recipe->getCursorUrl('abc123');

        self::assertSame('?cursor=abc123', $url);
    }

    public function testUrlWithRouteAndFragment()
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('app_items', ['page' => 2])
            ->willReturn('/items?page=2');

        $recipe = new PaginationUrlGenerator(
            route: 'app_items',
            urlGenerator: $urlGenerator,
        );

        $withFragment = $recipe->withFragment('results');
        $url = $withFragment->getUrl(2);

        self::assertSame('/items?page=2#results', $url);
    }

    // ── Path-based page parameter tests ──────────────────────

    public function testAutoDetectedRouteWithPageInPath()
    {
        $request = new Request();
        $request->attributes->set('_route', 'blog_list');
        $request->attributes->set('_route_params', ['page' => 2]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('blog_list', ['page' => 3])
            ->willReturn('/blog/3');

        $recipe = new PaginationUrlGenerator(
            requestStack: $requestStack,
            urlGenerator: $urlGenerator,
        );

        $url = $recipe->getUrl(3);

        self::assertSame('/blog/3', $url);
    }

    public function testAutoDetectedRoutePageOneOmitsPageParam()
    {
        $request = new Request();
        $request->attributes->set('_route', 'blog_list');
        $request->attributes->set('_route_params', ['page' => 3]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('blog_list', [])
            ->willReturn('/blog');

        $recipe = new PaginationUrlGenerator(
            requestStack: $requestStack,
            urlGenerator: $urlGenerator,
        );

        // Page 1 should omit page param entirely, route default handles it
        $url = $recipe->getUrl(1);

        self::assertSame('/blog', $url);
    }

    public function testAutoDetectedRoutePreservesOtherRouteParams()
    {
        $request = new Request();
        $request->attributes->set('_route', 'category_list');
        $request->attributes->set('_route_params', ['category' => 'php', 'page' => 1]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('category_list', ['category' => 'php', 'page' => 5])
            ->willReturn('/blog/php/5');

        $recipe = new PaginationUrlGenerator(
            requestStack: $requestStack,
            urlGenerator: $urlGenerator,
        );

        $url = $recipe->getUrl(5);

        self::assertSame('/blog/php/5', $url);
    }

    public function testCursorUrlWithAutoDetectedRoute()
    {
        $request = new Request();
        $request->attributes->set('_route', 'item_list');
        $request->attributes->set('_route_params', ['category' => 'books']);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::once())
            ->method('generate')
            ->with('item_list', ['category' => 'books', 'cursor' => 'abc123'])
            ->willReturn('/items/books?cursor=abc123');

        $recipe = new PaginationUrlGenerator(
            requestStack: $requestStack,
            urlGenerator: $urlGenerator,
        );

        $url = $recipe->getCursorUrl('abc123');

        self::assertSame('/items/books?cursor=abc123', $url);
    }

    public function testAbsoluteUrlUsesRequestSchemeAndHost()
    {
        $request = Request::create('https://example.com/items?page=2');
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $recipe = new PaginationUrlGenerator(requestStack: $requestStack);

        self::assertSame('https://example.com/items?page=3', $recipe->getAbsoluteUrl(3));
    }

    public function testAbsoluteUrlRequiresRequestOrRouterRoute()
    {
        $recipe = new PaginationUrlGenerator(basePath: '/items');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot generate an absolute pagination URL');

        $recipe->getAbsoluteUrl(3);
    }

    public function testPageOneFallsBackToExplicitParamWhenRouteRequiresIt()
    {
        $generator = $this->createMock(UrlGeneratorInterface::class);
        $generator->expects(self::exactly(2))
            ->method('generate')
            ->willReturnCallback(static function (string $route, array $params) {
                if (!isset($params['page'])) {
                    throw new \Symfony\Component\Routing\Exception\MissingMandatoryParametersException($route, ['page']);
                }

                return '/blog/'.$params['page'];
            });

        $recipe = new PaginationUrlGenerator(route: 'blog', urlGenerator: $generator);

        self::assertSame('/blog/1', $recipe->getUrl(1));
    }

    public function testMissingParamOtherThanPageStillThrows()
    {
        $generator = $this->createStub(UrlGeneratorInterface::class);
        $generator->method('generate')
            ->willThrowException(new \Symfony\Component\Routing\Exception\MissingMandatoryParametersException('blog', ['slug']));

        $recipe = new PaginationUrlGenerator(route: 'blog', urlGenerator: $generator);

        $this->expectException(\Symfony\Component\Routing\Exception\MissingMandatoryParametersException::class);

        $recipe->getUrl(2);
    }
}
