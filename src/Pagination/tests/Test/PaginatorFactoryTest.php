<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests\Test;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\UX\Pagination\Exception\InvalidCursorException;
use Symfony\UX\Pagination\Exception\OffsetLimitExceededException;
use Symfony\UX\Pagination\Test\PaginatorFactory;

#[CoversClass(PaginatorFactory::class)]
final class PaginatorFactoryTest extends TestCase
{
    public function testCursorSecretIsMarkedAsSensitive(): void
    {
        $parameters = new \ReflectionMethod(PaginatorFactory::class, 'create')->getParameters();
        $cursorSecret = array_find($parameters, static fn (\ReflectionParameter $parameter): bool => 'cursorSecret' === $parameter->getName());

        self::assertInstanceOf(\ReflectionParameter::class, $cursorSecret);
        self::assertNotEmpty($cursorSecret->getAttributes(\SensitiveParameter::class));
    }

    public function testCreatesDeterministicOffsetPagination(): void
    {
        $paginator = PaginatorFactory::create(defaultPerPage: 2);

        $pagination = $paginator->paginate(range(1, 5), page: 2);

        self::assertSame([3, 4], $pagination->getItems());
        self::assertSame(2, $pagination->getCurrentPage());
        self::assertSame(2, $pagination->getItemsPerPage());
        self::assertSame(5, $pagination->getTotalItems());
        self::assertSame('/?page=3', $pagination->getNextUrl());
        self::assertSame('/', $pagination->getPreviousUrl());
    }

    public function testUsesARequestForPageResolutionAndRealisticUrls(): void
    {
        $request = Request::create('https://example.test/products?category=books&page=2');
        $paginator = PaginatorFactory::create(request: $request, defaultPerPage: 2);

        $pagination = $paginator->paginate(range(1, 6));

        self::assertSame([3, 4], $pagination->getItems());
        self::assertSame('/products?category=books', $pagination->getPreviousUrl());
        self::assertSame('/products?category=books&page=3', $pagination->getNextUrl());
        self::assertSame('https://example.test/products?category=books&page=3', $pagination->getAbsoluteUrl(3));
    }

    public function testUsesAProvidedRequestStackAndCustomParameterNames(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/catalog?filter=active&offset=2'));

        $paginator = PaginatorFactory::create(
            requestStack: $requestStack,
            defaultPerPage: 2,
            defaultPageParam: 'offset',
            defaultCursorParam: 'after',
        );

        $pagination = $paginator->paginate(range(1, 6));

        self::assertSame([3, 4], $pagination->getItems());
        self::assertSame('/catalog?filter=active&offset=3', $pagination->getNextUrl());
    }

    public function testExplicitRequestBecomesCurrentOnAProvidedStack(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/old?page=3'));

        $paginator = PaginatorFactory::create(
            request: Request::create('/new?page=2'),
            requestStack: $requestStack,
            defaultPerPage: 2,
        );

        $pagination = $paginator->paginate(range(1, 6));

        self::assertSame([3, 4], $pagination->getItems());
        self::assertSame('/new?page=3', $pagination->getNextUrl());
        self::assertSame('/new?page=2', $requestStack->getCurrentRequest()?->getRequestUri());
    }

    public function testUsesARealUrlGeneratorForRoutes(): void
    {
        $routes = new RouteCollection();
        $routes->add('product_list', new Route('/catalog/{section}'));
        $urlGenerator = new UrlGenerator($routes, new RequestContext());

        $request = Request::create('/catalog/books?filter=active');
        $request->attributes->set('_route', 'product_list');
        $request->attributes->set('_route_params', ['section' => 'books']);

        $paginator = PaginatorFactory::create(
            request: $request,
            urlGenerator: $urlGenerator,
            defaultPerPage: 2,
        );

        $pagination = $paginator->paginate(range(1, 6));

        self::assertSame('/catalog/books?filter=active&page=2', $pagination->getNextUrl());
    }

    public function testForwardsTheMaximumOffsetGuard(): void
    {
        $paginator = PaginatorFactory::create(
            defaultPerPage: 2,
            defaultMaxOffset: 1,
        );

        $this->expectException(OffsetLimitExceededException::class);

        $paginator->paginate(range(1, 6), page: 2);
    }

    public function testSignedCursorUrlsRoundTripForwardAndBackward(): void
    {
        $source = $this->cursorSource();
        $first = PaginatorFactory::create(request: Request::create('/events?type=release'))
            ->cursor($source)
            ->orderBy('id', 'ASC')
            ->perPage(2)
            ->context('events')
            ->paginate();

        self::assertSame([1, 2], array_column($first->getItems(), 'id'));
        $nextUrl = $first->getNextUrl();
        self::assertNotNull($nextUrl);

        $sameFirstPage = PaginatorFactory::create(request: Request::create('/events?type=release'))
            ->cursor($source)
            ->orderBy('id', 'ASC')
            ->perPage(2)
            ->context('events')
            ->paginate();
        self::assertSame($first->getNextCursor(), $sameFirstPage->getNextCursor());

        $secondRequest = Request::create('https://example.test'.$nextUrl);
        $second = PaginatorFactory::create(request: $secondRequest)
            ->cursor($source)
            ->orderBy('id', 'ASC')
            ->perPage(2)
            ->context('events')
            ->paginate();

        self::assertSame([3, 4], array_column($second->getItems(), 'id'));
        $previousUrl = $second->getPreviousUrl();
        self::assertNotNull($previousUrl);
        self::assertStringStartsWith('/events?type=release&cursor=', $previousUrl);

        $previousRequest = Request::create('https://example.test'.$previousUrl);
        $again = PaginatorFactory::create(request: $previousRequest)
            ->cursor($source)
            ->orderBy('id', 'ASC')
            ->perPage(2)
            ->context('events')
            ->paginate();

        self::assertSame([1, 2], array_column($again->getItems(), 'id'));
    }

    public function testUsesACustomCursorParameterInRequestsAndUrls(): void
    {
        $source = $this->cursorSource();
        $first = PaginatorFactory::create(
            request: Request::create('/events?type=release'),
            defaultCursorParam: 'after',
        )
            ->cursor($source)
            ->orderBy('id', 'ASC')
            ->perPage(2)
            ->context('events')
            ->paginate();

        $nextUrl = $first->getNextUrl();
        self::assertNotNull($nextUrl);
        self::assertStringContainsString('after=', $nextUrl);
        self::assertStringNotContainsString('cursor=', $nextUrl);

        $second = PaginatorFactory::create(
            request: Request::create('https://example.test'.$nextUrl),
            defaultCursorParam: 'after',
        )
            ->cursor($source)
            ->orderBy('id', 'ASC')
            ->perPage(2)
            ->context('events')
            ->paginate();

        self::assertSame([3, 4], array_column($second->getItems(), 'id'));
    }

    public function testCursorSecretCanBeOverridden(): void
    {
        $source = $this->cursorSource();
        $first = PaginatorFactory::create(cursorSecret: 'first-test-secret')
            ->cursor($source)
            ->orderBy('id', 'ASC')
            ->perPage(2)
            ->context('events')
            ->paginate();
        $cursor = $first->getNextCursor();
        \assert(null !== $cursor);

        $builder = PaginatorFactory::create(cursorSecret: 'another-test-secret')
            ->cursor($source)
            ->orderBy('id', 'ASC')
            ->cursor($cursor)
            ->perPage(2)
            ->context('events');

        $this->expectException(InvalidCursorException::class);
        $this->expectExceptionMessage('Invalid cursor signature.');

        $builder->paginate();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function cursorSource(): array
    {
        return array_map(
            static fn (int $id): array => ['id' => $id, 'name' => 'Event '.$id],
            range(1, 6),
        );
    }
}
