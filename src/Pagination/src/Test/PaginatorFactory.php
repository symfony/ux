<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Test;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Pagination\Adapter\ArrayPaginationAdapter;
use Symfony\UX\Pagination\Cursor\CursorCodec;
use Symfony\UX\Pagination\Paginator;

/**
 * Creates a deterministic, fully functional paginator for application tests.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class PaginatorFactory
{
    private const DEFAULT_CURSOR_SECRET = 'symfony-ux-pagination-test-secret';

    public static function create(
        ?Request $request = null,
        ?RequestStack $requestStack = null,
        ?UrlGeneratorInterface $urlGenerator = null,
        int $defaultPerPage = 20,
        string $defaultPageParam = 'page',
        string $defaultCursorParam = 'cursor',
        #[\SensitiveParameter]
        string $cursorSecret = self::DEFAULT_CURSOR_SECRET,
        int $defaultMaxOffset = 100_000,
    ): Paginator {
        $requestStack ??= new RequestStack();

        if (null !== $request) {
            $requestStack->push($request);
        } elseif (null === $requestStack->getCurrentRequest()) {
            $requestStack->push(Request::create('/'));
        }

        return new Paginator(
            adapters: [new ArrayPaginationAdapter()],
            requestStack: $requestStack,
            urlGenerator: $urlGenerator,
            defaultPerPage: $defaultPerPage,
            defaultPageParam: $defaultPageParam,
            defaultCursorParam: $defaultCursorParam,
            cursorCodec: new CursorCodec($cursorSecret),
            defaultMaxOffset: $defaultMaxOffset,
        );
    }
}
