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
use Symfony\UX\Breadcrumb\Exception\LogicException;

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
     * @var list<Breadcrumb|\Closure(): iterable<Breadcrumb>|list<Breadcrumb>>
     */
    private array $entries = [];

    private bool $expanding = false;

    /**
     * @param array<string, mixed> $routeParameters
     * @param array<string, mixed> $context         controller arguments referenced by crumb expressions
     */
    public function __construct(
        public readonly string $route = '',
        public readonly array $routeParameters = [],
        public readonly array $context = [],
    ) {
    }

    /**
     * A closure is only called when the trail is read, so a request that renders no breadcrumb never runs it.
     *
     * @param Breadcrumb|\Closure(): iterable<Breadcrumb> ...$crumbs
     */
    public function append(Breadcrumb|\Closure ...$crumbs): void
    {
        foreach ($crumbs as $crumb) {
            $this->entries[] = $crumb;
        }
    }

    /**
     * @param Breadcrumb|\Closure(): iterable<Breadcrumb> ...$crumbs
     */
    public function prepend(Breadcrumb|\Closure ...$crumbs): void
    {
        $this->entries = array_values([...$crumbs, ...$this->entries]);
    }

    /**
     * Does not call the closures, so a trail holding one is not empty, even when the closure returns no crumb.
     */
    public function isEmpty(): bool
    {
        return [] === $this->entries;
    }

    /**
     * @return list<Breadcrumb>
     */
    public function all(): array
    {
        if ($this->expanding) {
            throw new LogicException('A closure added to the breadcrumb trail cannot read the trail it is added to.');
        }

        // A closure may add to the trail, which moves the entries, so its position is looked up again once it has run.
        while (null !== $closure = $this->nextClosure()) {
            $this->expanding = true;
            try {
                $expanded = self::call($closure);
            } finally {
                $this->expanding = false;
            }

            foreach ($this->entries as $index => $entry) {
                if ($entry === $closure) {
                    $this->entries[$index] = $expanded;
                    break;
                }
            }
        }

        $crumbs = [];
        foreach ($this->entries as $entry) {
            if ($entry instanceof Breadcrumb) {
                $crumbs[] = $entry;
            } elseif (\is_array($entry)) {
                array_push($crumbs, ...$entry);
            }
        }

        return $crumbs;
    }

    private function nextClosure(): ?\Closure
    {
        foreach ($this->entries as $entry) {
            if ($entry instanceof \Closure) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @return list<Breadcrumb>
     */
    private static function call(\Closure $closure): array
    {
        $result = $closure();
        if (!is_iterable($result)) {
            throw new LogicException(\sprintf('A closure added to the breadcrumb trail must return an iterable of "%s", "%s" given.', Breadcrumb::class, get_debug_type($result)));
        }

        $crumbs = [];
        foreach ($result as $crumb) {
            if (!$crumb instanceof Breadcrumb) {
                throw new LogicException(\sprintf('A closure added to the breadcrumb trail must return an iterable of "%s", but it holds a "%s".', Breadcrumb::class, get_debug_type($crumb)));
            }

            $crumbs[] = $crumb;
        }

        return $crumbs;
    }
}
