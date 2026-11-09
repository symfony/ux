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
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\Ternary\ConditionalTernary;
use Twig\Node\Node;
use Twig\Node\Nodes;
use Twig\TwigFunction;

/**
 * A css() call resolved when the template compiled: its classes, plus a runtime call for the part of the hash only known when it renders.
 *
 * It stays a function call for Twig, so that the escaper asks css() whether its classes need escaping.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class CssExpression extends FunctionExpression
{
    /**
     * @param list<AbstractExpression> $parts each one gives a space-separated list of classes
     */
    public function __construct(TwigFunction $function, array $parts, ?ArrayExpression $dynamic, int $lineno)
    {
        $nodes = ['parts' => new Nodes($parts)];
        if (null !== $dynamic) {
            $nodes['dynamic'] = $dynamic;
        }
        $arguments = new Nodes($nodes);
        $arguments->setAttribute('safe', null === $dynamic && self::needNoHtmlEscaping($parts) ? ['html'] : []);

        parent::__construct($function, $arguments, $lineno);
    }

    /**
     * @return list<string> the escaping strategies that would not change the classes of a resolved call, for the is_safe_callback of css()
     */
    public static function safeFor(Node $arguments): array
    {
        return $arguments->hasAttribute('safe') ? $arguments->getAttribute('safe') : [];
    }

    public function compile(Compiler $compiler): void
    {
        $arguments = $this->getNode('arguments');
        if ($arguments->hasNode('dynamic')) {
            $compiler->raw('$this->env->getRuntime(')->string(CssRuntime::class)->raw(')->append(');
        }

        $parts = iterator_to_array($arguments->getNode('parts'));
        if ([] === $parts) {
            $compiler->string('');
        }
        foreach (array_values($parts) as $index => $part) {
            $compiler->raw(0 === $index ? '(' : '." ".(')->subcompile($part)->raw(')');
        }

        if ($arguments->hasNode('dynamic')) {
            $compiler->raw(', ')->subcompile($arguments->getNode('dynamic'))->raw(')');
        }
    }

    /**
     * @param list<AbstractExpression> $parts
     */
    private static function needNoHtmlEscaping(array $parts): bool
    {
        foreach ($parts as $part) {
            $values = $part instanceof ConditionalTernary ? [$part->getNode('left'), $part->getNode('right')] : [$part];
            foreach ($values as $value) {
                // without the charset of the environment, only printable ASCII is known to stay the same
                if (!$value instanceof ConstantExpression || !preg_match('/^[^&<>"\'\x00-\x1F\x7F-\xFF]*$/', $value->getAttribute('value'))) {
                    return false;
                }
            }
        }

        return true;
    }
}
