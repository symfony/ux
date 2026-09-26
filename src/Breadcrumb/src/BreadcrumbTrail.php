<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb;

use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;

/**
 * The unresolved crumbs collected for the current request, plus the context needed to resolve them later.
 *
 * Nothing here is translated or turned into a URL: resolution happens in BreadcrumbResolver, driven from Twig, so a request that never renders a breadcrumb pays nothing.
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class BreadcrumbTrail
{
    public const string ATTRIBUTE = '_breadcrumbs';

    /**
     * @var list<Breadcrumb>
     */
    private array $crumbs = [];

    /**
     * @param array<string, mixed> $routeParameters the matched route parameters
     * @param array<string, mixed> $context         controller arguments referenced by crumb expressions
     */
    public function __construct(
        public readonly string $route = '',
        public readonly array $routeParameters = [],
        public readonly array $context = [],
    ) {
    }

    public function append(Breadcrumb ...$crumbs): void
    {
        foreach ($crumbs as $crumb) {
            $this->crumbs[] = $crumb;
        }
    }

    public function prepend(Breadcrumb ...$crumbs): void
    {
        $this->crumbs = array_values([...$crumbs, ...$this->crumbs]);
    }

    public function isEmpty(): bool
    {
        return [] === $this->crumbs;
    }

    /**
     * @return list<Breadcrumb>
     */
    public function all(): array
    {
        return $this->crumbs;
    }
}
