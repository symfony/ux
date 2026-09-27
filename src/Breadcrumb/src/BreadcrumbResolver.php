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

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;

/**
 * Turns a collected trail into the items a template renders.
 *
 * Intentionally interface-less: this one is internal to the package with a single caller (BreadcrumbExtension), no cross-layer inversion to satisfy and no test double.
 * The tests use the real service from the container.
 *
 * @phpstan-import-type Placeholder from LabelPattern
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
        private readonly PropertyAccessorInterface $propertyAccessor = new PropertyAccessor(),
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
            $items[] = new BreadcrumbItem(
                label: $this->resolveLabel($crumb, $trail),
                url: $this->resolveUrl($crumb, $trail, $index === $last, $referenceType),
                extra: $crumb->extra,
            );
        }

        return $items;
    }

    private function resolveLabel(Breadcrumb $crumb, BreadcrumbTrail $trail): string
    {
        if ($crumb->label instanceof Expression) {
            return $this->expressionLabel($crumb->label, $trail);
        }

        if ([] !== $placeholders = LabelPattern::parse($crumb->label)) {
            return $this->patternLabel($crumb->label, $placeholders, $trail);
        }

        return $this->translatedLabel($crumb->label, $crumb, $trail);
    }

    /**
     * `translationDomain` is a tri-state: null translates against the default domain, a string against that domain, and false leaves the label untouched.
     */
    private function translatedLabel(string $label, Breadcrumb $crumb, BreadcrumbTrail $trail): string
    {
        if (false === $crumb->translationDomain || null === $this->translator) {
            return $label;
        }

        return $this->translator->trans(
            $label,
            $this->translationParameters($crumb, $trail),
            $crumb->translationDomain ?? $this->defaultTranslationDomain,
        );
    }

    /**
     * The value is the label itself, not a translation key, unless it is a TranslatableInterface.
     * A value that cannot be evaluated or turned into a string degrades to an empty label.
     */
    private function expressionLabel(Expression $label, BreadcrumbTrail $trail): string
    {
        try {
            $value = $this->expressionLanguage->evaluate($label, $trail->context);
        } catch (\Throwable) {
            return '';
        }

        return $this->stringify($value);
    }

    /**
     * Each placeholder that cannot be read or turned into a string degrades to an empty string.
     *
     * @param non-empty-list<Placeholder> $placeholders
     */
    private function patternLabel(string $label, array $placeholders, BreadcrumbTrail $trail): string
    {
        $replacements = [];
        foreach ($placeholders as $placeholder) {
            $replacements[$placeholder['placeholder']] = $this->stringify($this->placeholderValue($placeholder, $trail));
        }

        return strtr($label, $replacements);
    }

    /**
     * @param Placeholder $placeholder
     */
    private function placeholderValue(array $placeholder, BreadcrumbTrail $trail): mixed
    {
        if (null === $argument = $placeholder['argument']) {
            return $trail->context[$placeholder['variable']] ?? $trail->routeParameters[$placeholder['variable']] ?? null;
        }

        if (!\is_object($subject = $trail->context[$argument] ?? null) && !\is_array($subject)) {
            return null;
        }

        try {
            return $this->propertyAccessor->getValue($subject, $placeholder['path'] ?? $placeholder['variable']);
        } catch (\Throwable) {
            return null;
        }
    }

    private function stringify(mixed $value): string
    {
        if ($value instanceof TranslatableInterface && null !== $this->translator) {
            return $value->trans($this->translator);
        }

        return \is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
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

        // The current page falls back to the matched route, so JSON-LD gets its absolute URL.
        $route = $crumb->route ?? ($isCurrent && '' !== $trail->route ? $trail->route : null);
        if (null === $route) {
            return null;
        }

        try {
            // Evaluation belongs inside the try: a failing expression must degrade, not throw.
            $parameters = $isCurrent && null === $crumb->route
                ? $trail->routeParameters
                : $this->urlParameters($crumb, $trail);

            return $this->urlGenerator->generate($route, $parameters, $referenceType);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * A bare name is taken from the matched route and skipped when the route has none. The keyed entries come after, so they win over a bare name.
     *
     * @return array<string, mixed>
     */
    private function urlParameters(Breadcrumb $crumb, BreadcrumbTrail $trail): array
    {
        $inherited = [];
        $keyed = [];
        foreach ($crumb->parameters as $key => $value) {
            if (!\is_int($key)) {
                $keyed[$key] = $value;
            } elseif (\is_string($value) && \array_key_exists($value, $trail->routeParameters)) {
                $inherited[$value] = $trail->routeParameters[$value];
            }
        }

        return [...$inherited, ...array_map(fn (mixed $value): mixed => $this->evaluate($value, $trail), $keyed)];
    }

    /**
     * A parameter that cannot be evaluated is left out, so the translator keeps its placeholder instead of failing on a value of the wrong type.
     * The other parameters are still passed.
     *
     * @return array<string, mixed>
     */
    private function translationParameters(Breadcrumb $crumb, BreadcrumbTrail $trail): array
    {
        $parameters = [];
        foreach ($crumb->translationParameters as $key => $value) {
            try {
                $parameters[$key] = $this->evaluate($value, $trail);
            } catch (\Throwable) {
            }
        }

        return $parameters;
    }

    /**
     * Evaluates an Expression and keeps any other value as given.
     */
    private function evaluate(mixed $value, BreadcrumbTrail $trail): mixed
    {
        return $value instanceof Expression ? $this->expressionLanguage->evaluate($value, $trail->context) : $value;
    }
}
