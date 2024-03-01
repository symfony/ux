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
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 *
 * CVA (class variant authority), is a concept from the js world.
 * https://cva.style/docs
 * The UI library shadcn is build on top of this principle
 * https://ui.shadcn.com
 * The concept behind CVA is to let you build component with a lot of different variations called recipes.
 *
 * @experimental
 */
final class CVA
{
    /**
     * @var string|list<string|null>|null
     * @var array<string, array<string, string>>|null the array should have the following format [variantCategory => [variantName => classes]]
     *                                                ex: ['colors' => ['primary' => 'bleu-8000', 'danger' => 'red-800 text-bold'], 'size' => [...]]
     * @var array<array<string, string[]>>|null       the array should have the following format ['variantsCategory' => ['variantName', 'variantName'], 'class' => 'text-red-500']
     * @var array<string, string>|null
     */
    public function __construct(
        private string|array|null $base = null,
        private ?array $variants = null,
        private ?array $compoundVariants = null,
        private ?array $defaultVariants = null,
    ) {
    }

    public function apply(array $recipes, string ...$classes): string
    {
        return trim($this->resolve($recipes).' '.implode(' ', $classes));
    }

    public function resolve(array $recipes): string
    {
        if (\is_array($this->base)) {
            $classes = implode(' ', $this->base);
        } else {
            $classes = $this->base ?? '';
        }

        foreach ($recipes as $recipeName => $recipeValue) {
            if (!isset($this->variants[$recipeName][$recipeValue])) {
                continue;
            }

            $classes .= ' '.$this->variants[$recipeName][$recipeValue];
        }

        if (null !== $this->compoundVariants) {
            foreach ($this->compoundVariants as $compound) {
                $isCompound = true;
                foreach ($compound as $compoundName => $compoundValues) {
                    if ('class' === $compoundName) {
                        continue;
                    }

                    if (!isset($recipes[$compoundName])) {
                        $isCompound = false;
                        break;
                    }

                    if (!\in_array($recipes[$compoundName], $compoundValues)) {
                        $isCompound = false;
                        break;
                    }
                }

                if ($isCompound) {
                    if (!isset($compound['class']) || !\is_string($compound['class'])) {
                        throw new \LogicException('A compound recipe matched but no classes are registered for this match');
                    }

                    $classes .= ' '.$compound['class'];
                }
            }
        }

        if (null !== $this->defaultVariants) {
            foreach ($this->defaultVariants as $defaultVariantName => $defaultVariantValue) {
                if (!isset($recipes[$defaultVariantName])) {
                    $classes .= ' '.$this->variants[$defaultVariantName][$defaultVariantValue];
                }
            }
        }

        return trim($classes);
    }
}
