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
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Nodes;

/**
 * A css() call resolved when the template compiled: its classes, plus a runtime call for the part of the hash only known when it renders.
 *
 * It is not a constant expression on purpose, so that Twig escapes it like any other string.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class CssExpression extends AbstractExpression
{
    /**
     * @param list<AbstractExpression> $parts each one gives a space-separated list of classes
     */
    public function __construct(array $parts, ?ArrayExpression $dynamic, int $lineno)
    {
        $nodes = ['parts' => new Nodes($parts)];
        if (null !== $dynamic) {
            $nodes['dynamic'] = $dynamic;
        }

        parent::__construct($nodes, [], $lineno);
    }

    public function compile(Compiler $compiler): void
    {
        if ($this->hasNode('dynamic')) {
            $compiler->raw('$this->env->getRuntime(')->string(CssRuntime::class)->raw(')->append(');
        }

        $parts = iterator_to_array($this->getNode('parts'));
        if ([] === $parts) {
            $compiler->string('');
        }
        foreach (array_values($parts) as $index => $part) {
            $compiler->raw(0 === $index ? '(' : '." ".(')->subcompile($part)->raw(')');
        }

        if ($this->hasNode('dynamic')) {
            $compiler->raw(', ')->subcompile($this->getNode('dynamic'))->raw(')');
        }
    }
}
