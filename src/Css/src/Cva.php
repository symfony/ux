<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css;

use Symfony\UX\Css\Engine\CvaRecipe;
use Symfony\UX\Css\Twig\CssRuntime;
use Twig\Error\RuntimeError;

/**
 * A recipe of styles with variants, as returned by the cva() Twig function.
 *
 * Call apply() with the variants to select: it returns the CSS classes of the base styles, of the selected variants and of the matching compound variants.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class Cva
{
    /**
     * @param array<string, array<string, string>> $classes the precomputed classes (class mapped to its merge key), by CvaRecipe::key()
     *
     * @internal
     */
    public function __construct(
        private readonly CvaRecipe $recipe,
        private readonly array $classes,
        private readonly CssRuntime $runtime,
        private readonly bool $debug,
    ) {
    }

    /**
     * @param array<array-key, mixed> $props the variants to select, by name
     *
     * @throws RuntimeError in debug, when a variant or a value is not declared
     */
    public function apply(array $props = []): CssClassList
    {
        if ($this->debug && null !== $message = $this->recipe->undeclared($props)) {
            throw new RuntimeError($message);
        }

        $key = $this->recipe->key($props);

        return new CssClassList($this->classes[$key] ?? $this->runtime->cvaClasses($this->recipe, $props, $key));
    }
}
