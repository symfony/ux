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

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;

/**
 * Turns a collected trail into the items a template renders.
 *
 * Intentionally interface-less: this one is internal to the package with a single caller (BreadcrumbExtension), no cross-layer inversion to satisfy and no test double.
 * The tests use the real service from the container.
 *
 * @internal
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class BreadcrumbResolver
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ExpressionLanguage $expressionLanguage,
        private readonly ?TranslatorInterface $translator = null,
        private readonly ?string $defaultTranslationDomain = null,
    ) {
    }

    /**
     * @return list<BreadcrumbItem>
     */
    public function resolve(BreadcrumbTrail $trail, int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): array
    {
        $crumbs = $trail->all();
        $last = \count($crumbs) - 1;

        $items = [];
        foreach ($crumbs as $index => $crumb) {
            $items[] = $this->resolveCrumb($crumb, $trail, $index === $last, $referenceType);
        }

        return $items;
    }

    private function resolveCrumb(
        Breadcrumb $crumb,
        BreadcrumbTrail $trail,
        bool $isCurrent,
        int $referenceType,
    ): BreadcrumbItem {
        return new BreadcrumbItem(
            label: $this->label($crumb, $trail),
            url: $this->resolveUrl($crumb, $trail, $isCurrent, $referenceType),
            extra: $crumb->extra,
        );
    }

    /**
     * `translationDomain` is a tri-state: null translates against the default domain, a string against that domain, and false leaves the label untouched.
     */
    private function label(Breadcrumb $crumb, BreadcrumbTrail $trail): string
    {
        if (false === $crumb->translationDomain || null === $this->translator) {
            return $crumb->label;
        }

        try {
            $parameters = $this->evaluate($crumb->translationParameters, $trail->context);
        } catch (\Throwable) {
            // An expression that cannot be evaluated leaves its placeholder in the
            // label rather than taking the page down.
            $parameters = [];
        }

        return $this->translator->trans(
            $crumb->label,
            $parameters,
            $crumb->translationDomain ?? $this->defaultTranslationDomain,
        );
    }

    /**
     * The current page is not a link, so it needs no URL, unless an absolute reference is asked for, where every entry must carry one (JSON-LD).
     */
    private function resolveUrl(
        Breadcrumb $crumb,
        BreadcrumbTrail $trail,
        bool $isCurrent,
        int $referenceType,
    ): ?string {
        $absolute = UrlGeneratorInterface::ABSOLUTE_URL === $referenceType;

        if ($isCurrent && !$absolute) {
            return null;
        }

        $route = $this->route($crumb, $trail, $isCurrent && $absolute);
        if (null === $route) {
            return null;
        }

        try {
            // Evaluation sits inside the try: an expression naming an argument this
            // action never received degrades to a link-less crumb, like a route that
            // cannot be generated.
            $parameters = $isCurrent && null === $crumb->route
                ? $trail->routeParameters
                : array_merge(
                    $this->inheritedParameters($crumb, $trail),
                    $this->evaluate($crumb->computedParameters, $trail->context),
                );

            return $this->urlGenerator->generate($route, $parameters, $referenceType);
        } catch (\Throwable) {
            return null;
        }
    }

    private function route(Breadcrumb $crumb, BreadcrumbTrail $trail, bool $fallBackToCurrentRoute): ?string
    {
        if (null !== $crumb->route) {
            return $crumb->route;
        }

        return $fallBackToCurrentRoute && '' !== $trail->route ? $trail->route : null;
    }

    /**
     * `Breadcrumb::$inheritedParameters` is a list of names to take from the matched route, not a map of values.
     *
     * @return array<string, mixed>
     */
    private function inheritedParameters(Breadcrumb $crumb, BreadcrumbTrail $trail): array
    {
        return array_filter(
            $trail->routeParameters,
            static fn (string $key): bool => \in_array($key, $crumb->inheritedParameters, true),
            \ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * @param array<string, string> $expressions
     * @param array<string, mixed>  $context
     *
     * @return array<string, mixed>
     */
    private function evaluate(array $expressions, array $context): array
    {
        return array_map(
            fn (string $expression): mixed => $this->expressionLanguage->evaluate($expression, $context),
            $expressions,
        );
    }
}
