<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

/**
 * Class Variant Authority (CVA) resolver.
 *
 * The CVA concept is used to render multiple variations of components, applying
 * a set of conditions and recipes to dynamically compose CSS class strings.
 *
 * @see https://cva.style/docs
 *
 * @doc https://symfony.com/bundles/ux-twig-component/current/index.html
 *
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 *
 * @experimental
 */
final class CVA
{
    /**
     * @var list<string|null>
     */
    private readonly array $base;

    /**
     * @param string|list<string|null> $base The base classes to apply to the component
     */
    public function __construct(
        string|array $base = [],
        /**
         * The variants to apply based on recipes.
         *
         * Format: [variantCategory => [variantName => classes]]
         *
         * Example:
         *      'colors' => [
         *          'primary' => 'bleu-8000',
         *          'danger' => 'red-800 text-bold',
         *       ],
         *      'size' => [...],
         *
         * @var array<string, array<string, string|list<string>>>
         */
        private readonly array $variants = [],

        /**
         * The compound variants to apply based on recipes.
         *
         * Format: [variantsCategory => ['variantName', 'variantName'], class: classes]
         *
         * Example:
         *   [
         *      'colors' => ['primary'],
         *      'size' => ['small'],
         *      'class' => 'text-red-500',
         *   ],
         *   [
         *      'size' => ['large'],
         *      'class' => 'font-weight-500',
         *   ]
         *
         * @var array<array<string, string|array<string>>>
         */
        private readonly array $compoundVariants = [],

        /**
         * The default variants to apply if specific recipes aren't provided.
         *
         * Format: [variantCategory => variantName]
         *
         * Example:
         *     'colors' => 'primary',
         *
         * @var array<string, string>
         */
        private readonly array $defaultVariants = [],
    ) {
        $this->base = (array) $base;
    }

    public function apply(array $recipes, ?string ...$additionalClasses): string
    {
        $classes = [...$this->base];

        // Resolve recipes against variants
        foreach ($recipes as $recipeName => $recipeValue) {
            if (\is_bool($recipeValue)) {
                $recipeValue = $recipeValue ? 'true' : 'false';
            }
            $recipeClasses = $this->variants[$recipeName][$recipeValue] ?? [];
            $classes = [...$classes, ...(array) $recipeClasses];
        }

        // Resolve compound variants
        foreach ($this->compoundVariants as $compound) {
            $compoundClasses = $this->resolveCompoundVariant($compound, $recipes) ?? [];
            $classes = [...$classes, ...$compoundClasses];
        }

        // Apply default variants if specific recipes aren't provided
        foreach ($this->defaultVariants as $defaultVariantName => $defaultVariantValue) {
            if (!isset($recipes[$defaultVariantName])) {
                $variantClasses = $this->variants[$defaultVariantName][$defaultVariantValue] ?? [];
                $classes = [...$classes, ...(array) $variantClasses];
            }
        }
        $classes = [...$classes, ...array_values($additionalClasses)];

        $classes = implode(' ', array_filter($classes, is_string(...)));
        $classes = preg_split('#\s+#', $classes, -1, \PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' ', array_unique($classes));
    }

    private function resolveCompoundVariant(array $compound, array $recipes): array
    {
        foreach ($compound as $compoundName => $compoundValues) {
            if ('class' === $compoundName) {
                continue;
            }
            if (!isset($recipes[$compoundName]) || !\in_array($recipes[$compoundName], (array) $compoundValues)) {
                return [];
            }
        }

        return (array) ($compound['class'] ?? []);
    }
}
