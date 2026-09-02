<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Twig;

use Symfony\UX\Pagination\CursorPaginationInterface;
use Symfony\UX\Pagination\Exception\InvalidArgumentException;
use Symfony\UX\Pagination\NumberedPaginationInterface;
use Symfony\UX\Pagination\PaginationInterface;
use Twig\Environment;

/**
 * Renders a Pagination object to HTML.
 *
 * Used by the ux_pagination() Twig function and the ux:pagination component.
 *
 * @internal
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class PaginationRenderer
{
    public function __construct(
        private readonly Environment $twig,
        private readonly string $defaultTheme = '@UXPagination/theme/default.html.twig',
    ) {
    }

    /**
     * @param PaginationInterface<mixed>       $pagination
     * @param array<array-key, mixed>          $attributes
     * @param array<array-key, mixed>          $navigationAttributes
     * @param array<array-key, mixed>|\Closure $linkAttributes
     */
    public function renderPagination(
        PaginationInterface $pagination,
        array $attributes = [],
        ?string $theme = null,
        bool $showInfo = true,
        array $navigationAttributes = [],
        array|\Closure $linkAttributes = [],
    ): string {
        $theme ??= $this->defaultTheme;
        if ('' === trim($theme)) {
            throw new InvalidArgumentException('The "theme" argument must be a non-empty string or null.');
        }

        $numbered = $pagination instanceof NumberedPaginationInterface;

        $context = [
            'pagination' => $pagination,
            'attributes' => $this->normalizeAttributes($attributes, 'attributes'),
            'navigation_attributes' => $this->normalizeAttributes($navigationAttributes, 'navigationAttributes'),
            'show_info' => $showInfo,
            'numbered' => $numbered,
            'link_attributes' => $this->resolveLinkAttributes($pagination, $linkAttributes),
        ];

        return $this->twig->render($theme, $context);
    }

    /**
     * @param PaginationInterface<mixed>       $pagination
     * @param array<array-key, mixed>|\Closure $attributes
     *
     * @return array{
     *     previous: array<string, mixed>,
     *     pages: array<int, array<string, mixed>>,
     *     next: array<string, mixed>,
     * }
     */
    private function resolveLinkAttributes(PaginationInterface $pagination, array|\Closure $attributes): array
    {
        if ([] === $attributes) {
            return ['previous' => [], 'pages' => [], 'next' => []];
        }

        /**
         * @param array{
         *     relation: 'previous'|'page'|'next',
         *     url: string,
         *     page: int|null,
         *     cursor: string|null,
         * } $context
         */
        $resolve = function (array $context) use ($attributes): array {
            $resolved = $attributes instanceof \Closure ? $attributes($context) : $attributes;
            if (!\is_array($resolved)) {
                throw new InvalidArgumentException('The "linkAttributes" callable must return an array.');
            }

            return $this->normalizeAttributes($resolved, 'linkAttributes', forbidHref: true);
        };

        $previous = [];
        if ($pagination->hasPrevious()) {
            $previous = $resolve($this->createLinkContext($pagination, 'previous'));
        }

        $pages = [];
        if ($pagination instanceof NumberedPaginationInterface) {
            foreach ($pagination->getPages() as $link) {
                if (!$link->isGap() && !$link->isCurrent()) {
                    $pages[$link->getPage()] = $resolve([
                        'relation' => 'page',
                        'url' => $link->getUrl(),
                        'page' => $link->getPage(),
                        'cursor' => null,
                    ]);
                }
            }
        }

        $next = [];
        if ($pagination->hasNext()) {
            $next = $resolve($this->createLinkContext($pagination, 'next'));
        }

        return ['previous' => $previous, 'pages' => $pages, 'next' => $next];
    }

    /**
     * @param PaginationInterface<mixed> $pagination
     * @param 'previous'|'next'          $relation
     *
     * @return array{relation: 'previous'|'next', url: string, page: ?int, cursor: ?string}
     */
    private function createLinkContext(PaginationInterface $pagination, string $relation): array
    {
        $previous = 'previous' === $relation;
        $page = $pagination instanceof NumberedPaginationInterface
            ? $pagination->getCurrentPage() + ($previous ? -1 : 1)
            : null;
        $cursor = $pagination instanceof CursorPaginationInterface
            ? ($previous ? $pagination->getPreviousCursor() : $pagination->getNextCursor())
            : null;
        $url = $previous ? $pagination->getPreviousUrl() : $pagination->getNextUrl();
        if (null === $url) {
            $message = \sprintf('Pagination reports a "%s" link without a URL.', $relation);

            throw new InvalidArgumentException($message);
        }

        return [
            'relation' => $relation,
            'url' => $url,
            'page' => $page,
            'cursor' => $cursor,
        ];
    }

    /**
     * @param array<array-key, mixed> $attributes
     *
     * @return array<string, bool|float|int|string|null>
     */
    private function normalizeAttributes(array $attributes, string $argument, bool $forbidHref = false): array
    {
        $normalized = [];

        foreach ($attributes as $name => $value) {
            if (!\is_string($name) || '' === $name || 1 !== preg_match('/^[^\s"\'<>\/=]+$/uD', $name)) {
                $message = \sprintf('Invalid attribute name in "%s".', $argument);

                throw new InvalidArgumentException($message);
            }
            if ($forbidHref && 'href' === strtolower($name)) {
                throw new InvalidArgumentException('The "linkAttributes" option cannot override the "href" attribute.');
            }
            $normalizedName = 'class' === strtolower($name) ? 'class' : $name;
            if ('class' === $normalizedName
                && null !== $value
                && !\is_string($value)
                && !$value instanceof \Stringable
            ) {
                $message = \sprintf('Invalid "class" value in "%s"; expected string or null.', $argument);

                throw new InvalidArgumentException($message);
            }
            if (null !== $value && !\is_scalar($value) && !$value instanceof \Stringable) {
                $message = \sprintf('Attribute "%s" in "%s" is not scalar or Stringable.', $name, $argument);

                throw new InvalidArgumentException($message);
            }

            if ($value instanceof \Stringable) {
                $value = (string) $value;
            }

            $normalized[$normalizedName] = $value;
        }

        return $normalized;
    }
}
