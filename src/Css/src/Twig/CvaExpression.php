<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Twig;

use Twig\Compiler;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Nodes;
use Twig\TwigFunction;

/**
 * A cva() call resolved when the template compiled, with the classes of every combination of its variants when they are few enough.
 *
 * It stays a function call for Twig, so that the sandbox checks cva().
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class CvaExpression extends FunctionExpression
{
    /**
     * @param array<array-key, mixed>              $config
     * @param array<string, array<string, string>> $classes the precomputed classes by CvaRecipe::key()
     */
    public function __construct(TwigFunction $function, array $config, string $id, array $classes, int $lineno)
    {
        parent::__construct($function, new Nodes(), $lineno);
        $this->setAttribute('config', $config);
        $this->setAttribute('id', $id);
        $this->setAttribute('classes', $classes);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->raw('$this->env->getRuntime(')
            ->string(CssRuntime::class)
            ->raw(')->compiledCva(')
            ->repr($this->getAttribute('config'))
            ->raw(', ')
            ->repr($this->getAttribute('id'))
            ->raw(', ')
            ->repr($this->getAttribute('classes'))
            ->raw(')');
    }
}
