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

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\UX\Breadcrumb\Exception\InvalidArgumentException;
use Symfony\UX\Breadcrumb\LabelPattern;

/**
 * Declares one crumb of the breadcrumb trail of a controller.
 *
 * Repeatable: declare the crumbs in trail order, top to bottom.
 * Class-level crumbs come before method-level ones, so a classic controller can declare the shared head of the trail on the class and the leaf on each action.
 * A crumb that names a `$parent` gets that controller's trail above its own, so each controller declares only its own crumb.
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final class Breadcrumb
{
    /**
     * `$label` is a translation key, a literal label when `$translationDomain` is `false`, a pattern, or an `Expression` evaluated against the controller's arguments.
     * A pattern holds placeholders with the syntax of a #[Route] path: `{slug}` reads the controller argument `$slug`, or else the route parameter,
     * and `{name:product}` reads the property `name` of the controller argument `$product`.
     * Neither a pattern nor the value of an `Expression` is a translation key: they are the label itself, unless a value is a `TranslatableInterface`, which is translated.
     *
     * `$parameters` holds the URL parameters. Each entry says where its value comes from:
     *
     * - a **bare name** (an integer key, such as `'slug'`) takes the value from the already-matched route (`_route_params`);
     * - an **`Expression`** value is evaluated against the controller's arguments;
     * - **any other value** is used as given. A crumb built in PHP already holds its values, so it needs nothing else.
     *
     * The URL generator places each name in the path when the route declares a placeholder for it, and in the query string otherwise.
     * A name given both bare and as a key takes the keyed value.
     *
     * `$translationParameters` follows the same value rules, minus the bare names, but it feeds the translator rather than the URL.
     *
     * `$parent` names the controller whose trail goes above this crumb: an invokable controller class, an action (`[Controller::class, 'method']`) or a route name.
     * Only the first crumb of a class or of a method may name it. On a method, it replaces the class-level crumbs of that action.
     * The ancestors are resolved against the current request, so an ancestor that should be a link needs its own `$route` and URL parameters.
     *
     * @param array<int|string, mixed>                $parameters
     * @param array<string, mixed>                    $translationParameters
     * @param array<string, mixed>                    $extra                 forwarded as-is to the resolved BreadcrumbItem, never read here
     * @param string|array{class-string, string}|null $parent
     */
    public function __construct(
        public readonly string|Expression $label,
        public readonly ?string $route = null,
        public readonly array $parameters = [],
        public readonly string|false|null $translationDomain = null,
        public readonly array $translationParameters = [],
        public readonly array $extra = [],
        public readonly string|array|null $parent = null,
    ) {
        if (\is_string($translationDomain) || [] !== $translationParameters) {
            if ($label instanceof Expression) {
                throw new InvalidArgumentException('An Expression label is not a translation key: it takes no translation domain and no translation parameters. Make the expression return a TranslatableInterface to translate it.');
            }

            if ([] !== LabelPattern::parse($label)) {
                throw new InvalidArgumentException(\sprintf('The label "%s" holds placeholders, so it is not a translation key: it takes no translation domain and no translation parameters. Use a translation key and "translationParameters" to translate it.', $label));
            }
        }

        foreach ($parameters as $key => $value) {
            if (\is_int($key) && (!\is_string($value) || '' === $value)) {
                throw new InvalidArgumentException(\sprintf('A URL parameter without a key must be the non-empty name of a route parameter to inherit, "%s" given.', get_debug_type($value)));
            }
        }

        foreach ($translationParameters as $key => $value) {
            if (\is_int($key)) {
                throw new InvalidArgumentException(\sprintf('Translation parameters must be keyed by their placeholder name, integer key "%d" given.', $key));
            }
        }
    }
}
