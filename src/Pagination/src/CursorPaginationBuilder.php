<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\UX\Pagination\Adapter\CursorAdapterInterface;
use Symfony\UX\Pagination\Adapter\PaginationAdapterInterface;
use Symfony\UX\Pagination\Cursor\CursorBoundary;
use Symfony\UX\Pagination\Cursor\CursorCodecInterface;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\Exception\InvalidCursorException;
use Symfony\UX\Pagination\Navigation\PaginationUrlGenerator;

/**
 * Immutable builder for cursor pagination.
 *
 * Configure a stable order, cursor context and URL policy before paginate().
 * Every configuration method returns a new instance.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class CursorPaginationBuilder
{
    /** @var list<string> */
    private ?array $fields = null;
    private ?string $direction = null;
    private int $perPage;
    private ?string $cursor = null;
    private bool $cursorExplicit = false;
    private ?string $context = null;
    private PaginationUrlGenerator $paginationUrlGenerator;

    /**
     * @param iterable<PaginationAdapterInterface> $adapters
     */
    public function __construct(
        private readonly mixed $source,
        private readonly iterable $adapters,
        private readonly CursorCodecInterface $codec,
        private readonly ?RequestStack $requestStack = null,
        ?UrlGeneratorInterface $urlGenerator = null,
        private readonly ?PaginationInfoFormatter $infoFormatter = null,
        int $defaultPerPage = 20,
        string $defaultPageParam = 'page',
        string $defaultCursorParam = 'cursor',
    ) {
        if (\PHP_INT_MAX === $defaultPerPage) {
            throw new InvalidArgumentException('perPage must be less than PHP_INT_MAX.');
        }

        $this->perPage = $defaultPerPage;
        $this->paginationUrlGenerator = new PaginationUrlGenerator(
            queryParam: $defaultPageParam,
            requestStack: $requestStack,
            urlGenerator: $urlGenerator,
            cursorParam: $defaultCursorParam,
        );
    }

    /**
     * @param string|array<string> $fields
     */
    public function orderBy(string|array $fields, string $direction = 'DESC'): self
    {
        $fields = \is_array($fields) ? array_values($fields) : [$fields];
        if ([] === $fields || array_any($fields, static fn (mixed $field): bool => !\is_string($field) || '' === $field)) {
            throw new InvalidArgumentException('Cursor order fields must be non-empty strings.');
        }
        $direction = strtoupper($direction);
        if (!\in_array($direction, ['ASC', 'DESC'], true)) {
            throw new InvalidArgumentException('direction must be "ASC" or "DESC".');
        }

        $clone = clone $this;
        $clone->fields = $fields;
        $clone->direction = $direction;

        return $clone;
    }

    public function perPage(int $perPage): self
    {
        if ($perPage < 1) {
            throw new InvalidArgumentException('perPage must be >= 1.');
        }
        if (\PHP_INT_MAX === $perPage) {
            throw new InvalidArgumentException('perPage must be less than PHP_INT_MAX.');
        }
        $clone = clone $this;
        $clone->perPage = $perPage;

        return $clone;
    }

    public function cursor(?string $cursor): self
    {
        $clone = clone $this;
        $clone->cursor = $cursor;
        $clone->cursorExplicit = true;

        return $clone;
    }

    public function cursorParameter(string $name): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withCursorParameter($name);

        return $clone;
    }

    public function context(string $context): self
    {
        if ('' === $context) {
            throw new InvalidArgumentException('Cursor context must not be empty.');
        }
        $clone = clone $this;
        $clone->context = $context;

        return $clone;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function route(string $route, array $params = []): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withRoute($route, $params);

        return $clone;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function queryParameters(array $params): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withQueryParameters($params);

        return $clone;
    }

    public function preserveQueryString(): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withQueryString();

        return $clone;
    }

    public function discardQueryString(): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withoutQueryString();

        return $clone;
    }

    public function excludeQueryParameters(string ...$names): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withoutQueryParameters(...$names);

        return $clone;
    }

    public function fragment(string $fragment): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withFragment($fragment);

        return $clone;
    }

    public function path(string $path): self
    {
        $clone = clone $this;
        $clone->paginationUrlGenerator = $this->paginationUrlGenerator->withPath($path);

        return $clone;
    }

    /**
     * @return CursorPagination<mixed>
     *
     * @throws InvalidCursorException when the incoming cursor is malformed, tampered with, or bound to another order or context
     */
    public function paginate(): CursorPagination
    {
        $adapter = $this->resolveAdapter();
        $cursor = $this->cursorExplicit ? $this->cursor : $this->resolveCursorFromRequest();
        $context = $adapter->getCursorContext($this->source, $this->context);
        $order = $adapter->resolveCursorOrder($this->source, $this->fields, $this->direction);

        // Decode eagerly: an invalid cursor must fail here, in a request
        // context, not during template rendering where Twig wraps the
        // exception and its HTTP status is lost.
        $boundary = null;
        if (null !== $cursor) {
            try {
                $decoded = $this->codec->decode($cursor, $order->getFingerprint(), $context);
            } catch (\InvalidArgumentException $exception) {
                throw new InvalidCursorException($exception->getMessage(), $exception);
            }
            $boundary = new CursorBoundary($decoded['values'], $decoded['forward']);
        }

        return new CursorPagination(
            source: $this->source,
            adapter: $adapter,
            cursor: $cursor,
            perPage: $this->perPage,
            order: $order,
            cursorCodec: $this->codec,
            context: $context,
            paginationUrlGenerator: $this->paginationUrlGenerator,
            infoFormatter: $this->infoFormatter,
            boundary: $boundary,
        );
    }

    private function resolveCursorFromRequest(): ?string
    {
        $request = $this->requestStack?->getCurrentRequest();
        $cursorParam = $this->paginationUrlGenerator->getCursorParameterName();
        if (null === $request || !$request->query->has($cursorParam)) {
            return null;
        }
        $value = $request->query->all()[$cursorParam];
        if (!\is_string($value) || '' === $value || \strlen($value) > 4096) {
            throw new InvalidCursorException(\sprintf('The "%s" cursor parameter must be a non-empty string of at most 4096 bytes.', $cursorParam));
        }

        return $value;
    }

    private function resolveAdapter(): CursorAdapterInterface
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter instanceof CursorAdapterInterface && $adapter->supports($this->source)) {
                return $adapter;
            }
        }

        throw new InvalidArgumentException(\sprintf('No cursor pagination adapter found for source of type "%s". Register an adapter implementing "%s".', get_debug_type($this->source), CursorAdapterInterface::class));
    }
}
