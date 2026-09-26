<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Attribute;

/**
 * Declares one crumb of the breadcrumb trail of a controller.
 *
 * Repeatable: declare the crumbs in trail order, top to bottom.
 * Class-level crumbs come before method-level ones, so a classic controller can declare the shared head of the trail on the class and the leaf on each action.
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Breadcrumb
{
    /**
     * The three URL parameter bags differ in where the value comes from, not in where it goes:
     *
     * - `$parameters` is a **map of values**, used as given. A crumb built in PHP already holds them, so it needs nothing else.
     * - `$inheritedParameters` is a **list of names** taken from the already-matched route (`_route_params`). The values exist, so nothing is evaluated.
     * - `$computedParameters` is a **map** whose values are ExpressionLanguage expressions evaluated against the controller's arguments.
     *
     * None of them decides whether a parameter lands in the path or in the query string.
     * The URL generator places each name in the path when the route declares a placeholder for it, and in the query string otherwise.
     * When several bags name the same parameter, the last of that list wins: a given value overrides an inherited name, and a computed one overrides both.
     *
     * `$translationParameters` is a map of expressions too, but it feeds the translator rather than the URL.
     *
     * @param array<string, mixed>  $parameters
     * @param array<int, string>    $inheritedParameters
     * @param array<string, string> $computedParameters
     * @param array<string, string> $translationParameters
     * @param array<string, mixed>  $extra                 forwarded as-is to the resolved BreadcrumbItem, never read here
     */
    public function __construct(
        public readonly string $label,
        public readonly ?string $route = null,
        public readonly array $parameters = [],
        public readonly array $inheritedParameters = [],
        public readonly array $computedParameters = [],
        public readonly string|false|null $translationDomain = null,
        public readonly array $translationParameters = [],
        public readonly array $extra = [],
    ) {
    }
}
