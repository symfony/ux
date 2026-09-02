<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests\LiveComponent;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;
use Symfony\UX\Pagination\Adapter\ArrayPaginationAdapter;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\LiveComponent\ComponentWithPaginationTrait;
use Symfony\UX\Pagination\PaginationBuilder;
use Symfony\UX\Pagination\Paginator;

#[CoversTrait(ComponentWithPaginationTrait::class)]
final class ComponentWithPaginationTraitTest extends TestCase
{
    public function testDefaultState()
    {
        self::assertSame(1, $this->createComponent([1, 2, 3])->page);
    }

    public function testPageIsWritableAndSynchronizedWithTheUrl()
    {
        $property = new \ReflectionProperty(ComponentWithPaginationTrait::class, 'page');
        $attributes = $property->getAttributes(LiveProp::class);

        self::assertCount(1, $attributes);

        $liveProp = $attributes[0]->newInstance();

        self::assertTrue($liveProp->isIdentityWritable());
        self::assertInstanceOf(UrlMapping::class, $liveProp->url());
    }

    #[DataProvider('liveActions')]
    public function testNavigationMethodsAreLiveActions(string $method)
    {
        $attributes = new \ReflectionMethod(ComponentWithPaginationTrait::class, $method)
            ->getAttributes(LiveAction::class);

        self::assertCount(1, $attributes);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function liveActions(): iterable
    {
        yield 'go to page' => ['goToPage'];
        yield 'next page' => ['nextPage'];
        yield 'previous page' => ['previousPage'];
    }

    public function testBuilderOwnsTheSourceAndPageSize()
    {
        $component = $this->createComponent(range(1, 50));
        $component->page = 2;

        $pagination = $component->getPagination();

        self::assertSame(range(11, 20), $pagination->getItems());
        self::assertSame(2, $pagination->getCurrentPage());
        self::assertSame(10, $pagination->getItemsPerPage());
    }

    public function testGetPaginationCachesResultForTheCurrentPage()
    {
        $callCount = 0;
        $component = $this->createComponent(range(1, 50), $callCount);

        self::assertSame($component->getPagination(), $component->getPagination());
        self::assertSame(1, $callCount);
    }

    public function testChangingThePublicPageInvalidatesTheCachedResult()
    {
        $callCount = 0;
        $component = $this->createComponent(range(1, 100), $callCount);

        $component->getPagination();
        $component->page = 2;
        $component->getPagination();

        self::assertSame(2, $callCount);
    }

    public function testGoToPageChangesStateAndClearsCache()
    {
        $callCount = 0;
        $component = $this->createComponent(range(1, 100), $callCount);
        $component->getPagination();

        $component->goToPage(3);

        self::assertSame(3, $component->page);
        self::assertSame(21, $component->getPagination()->getItems()[0]);
        self::assertSame(2, $callCount);
    }

    public function testGoToPageRejectsInvalidState()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Page must be greater than or equal to 1.');

        $this->createComponent(range(1, 100))->goToPage(0);
    }

    public function testNextAndPreviousPageRespectAvailableNavigation()
    {
        $component = $this->createComponent(range(1, 25));

        $component->nextPage();
        self::assertSame(2, $component->page);

        $component->previousPage();
        self::assertSame(1, $component->page);

        $component->previousPage();
        self::assertSame(1, $component->page);

        $component->page = 3;
        $component->nextPage();
        self::assertSame(3, $component->page);
    }

    public function testResetPageIsAvailableForFilterChanges()
    {
        $component = $this->createComponent(range(1, 100));
        $component->page = 4;

        $component->resetPage();

        self::assertSame(1, $component->page);
    }

    public function testPaginationLinkAttributesBridgeLinksToTheLiveAction()
    {
        $attributes = $this->createComponent(range(1, 50))
            ->getPaginationLinkAttributes()([
                'relation' => 'page',
                'url' => '/products/3',
                'page' => 3,
                'cursor' => null,
            ]);

        self::assertSame([
            'data-action' => 'live#action:prevent',
            'data-live-action-param' => 'goToPage',
            'data-live-page-param' => 3,
        ], $attributes);
    }

    public function testPagePropUrlParameterFollowsTheConfiguredPageParameter()
    {
        $paginator = new Paginator([new ArrayPaginationAdapter()]);
        $component = new class($paginator) {
            use ComponentWithPaginationTrait;

            public function __construct(private readonly Paginator $paginator)
            {
            }

            protected function createPagination(): PaginationBuilder
            {
                return $this->paginator->query(range(1, 10))->pageParameter('p');
            }
        };

        $liveProp = new LiveProp(writable: true, url: true, modifier: 'modifyPageProp');
        $url = $component->modifyPageProp($liveProp)->url();

        self::assertInstanceOf(UrlMapping::class, $url);
        self::assertSame('p', $url->as);
        self::assertFalse($url->mapPath);
    }

    public function testCapturesThePageRouteAndKeepsItDuringLiveRequests()
    {
        $routes = new RouteCollection();
        $routes->add('demo_page', new Route('/demo'));
        $urlGenerator = new UrlGenerator($routes, new RequestContext());

        $stack = new RequestStack();
        $pageRequest = Request::create('/demo');
        $pageRequest->attributes->set('_route', 'demo_page');
        $pageRequest->attributes->set('_route_params', []);
        $stack->push($pageRequest);

        $component = $this->createRoutedComponent($stack, $urlGenerator);
        $component->setPaginationRequestStack($stack);
        $component->capturePaginationRoute();

        self::assertSame('demo_page', $component->paginationRoute);

        // Live re-render: the current request now targets the internal component route
        $stack->pop();
        $liveRequest = Request::create('/_components/foo');
        $liveRequest->attributes->set('_route', 'ux_live_component');
        $stack->push($liveRequest);

        $component->goToPage(2);

        self::assertSame('/demo?page=3', $component->getPagination()->getNextUrl());
    }

    public function testAnAlreadyCapturedRouteIsNeverOverwritten()
    {
        $stack = new RequestStack();
        $otherPageRequest = Request::create('/other');
        $otherPageRequest->attributes->set('_route', 'other_page');
        $stack->push($otherPageRequest);

        $component = $this->createRoutedComponent($stack, new UrlGenerator(new RouteCollection(), new RequestContext()));
        $component->setPaginationRequestStack($stack);
        $component->paginationRoute = 'captured_page';

        $component->capturePaginationRoute();

        self::assertSame('captured_page', $component->paginationRoute);
    }

    public function testNeverCapturesTheInternalLiveComponentRoute()
    {
        $stack = new RequestStack();
        $liveRequest = Request::create('/_components/foo');
        $liveRequest->attributes->set('_route', 'ux_live_component');
        $stack->push($liveRequest);

        $component = $this->createRoutedComponent($stack, new UrlGenerator(new RouteCollection(), new RequestContext()));
        $component->setPaginationRequestStack($stack);
        $component->capturePaginationRoute();

        self::assertNull($component->paginationRoute);
    }

    private function createRoutedComponent(RequestStack $stack, UrlGenerator $urlGenerator): object
    {
        $paginator = new Paginator(
            [new ArrayPaginationAdapter()],
            requestStack: $stack,
            urlGenerator: $urlGenerator,
        );

        return new class($paginator) {
            use ComponentWithPaginationTrait;

            public function __construct(private readonly Paginator $paginator)
            {
            }

            protected function createPagination(): PaginationBuilder
            {
                return $this->paginator
                    ->query(range(1, 30))
                    ->perPage(10);
            }
        };
    }

    public function testGoToPageArgumentIsExplicitlyMapped()
    {
        $parameter = new \ReflectionMethod(ComponentWithPaginationTrait::class, 'goToPage')->getParameters()[0];
        $attributes = $parameter->getAttributes(LiveArg::class);

        self::assertCount(1, $attributes);
        self::assertSame('page', $attributes[0]->newInstance()->name);
    }

    /**
     * @param list<mixed> $source
     */
    private function createComponent(array $source, ?int &$createCallCount = null): object
    {
        $createCallCount ??= 0;
        $paginator = new Paginator([new ArrayPaginationAdapter()]);

        return new class($source, $paginator, $createCallCount) {
            use ComponentWithPaginationTrait;

            /**
             * @param list<mixed> $source
             */
            public function __construct(
                private readonly array $source,
                private readonly Paginator $paginator,
                private int &$createCallCount,
            ) {
            }

            protected function createPagination(): PaginationBuilder
            {
                ++$this->createCallCount;

                return $this->paginator
                    ->query($this->source)
                    ->perPage(10);
            }
        };
    }
}
