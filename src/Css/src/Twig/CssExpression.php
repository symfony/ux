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

use Symfony\UX\Css\CssClassList;
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
     * @param list<AbstractExpression> $parts each one gives an array of classes mapped to their merge key
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
        $this->setAttribute('printed', false);
    }

    /**
     * @return list<string> the escaping strategies that would not change the classes of a resolved call, for the is_safe_callback of css()
     */
    public static function safeFor(Node $arguments): array
    {
        return $arguments->hasAttribute('safe') ? $arguments->getAttribute('safe') : [];
    }

    /**
     * Compiles a printed call to its classes as a string, as only merging attributes needs its CssClassList.
     */
    public function printAsString(): void
    {
        $arguments = $this->getNode('arguments');
        if (!$arguments->hasNode('dynamic') && self::joinsLikeAClassList($arguments->getNode('parts'))) {
            $this->setAttribute('printed', true);
        }
    }

    public function compile(Compiler $compiler): void
    {
        $arguments = $this->getNode('arguments');
        if ($this->getAttribute('printed')) {
            foreach (array_values(iterator_to_array($arguments->getNode('parts'))) as $index => $part) {
                $compiler->raw(0 === $index ? '(' : '." ".(')->subcompile(self::toString($part))->raw(')');
            }

            return;
        }

        if ($arguments->hasNode('dynamic')) {
            $compiler->raw('$this->env->getRuntime(')->string(CssRuntime::class)->raw(')->append(');
        } else {
            $compiler->raw('new \\'.CssClassList::class.'(');
        }

        $compiler->raw('[');
        foreach (array_values(iterator_to_array($arguments->getNode('parts'))) as $index => $part) {
            $compiler->raw(0 === $index ? '...(' : ', ...(')->subcompile($part)->raw(')');
        }
        $compiler->raw(']');

        if ($arguments->hasNode('dynamic')) {
            $compiler->raw(', ')->subcompile($arguments->getNode('dynamic'));
        }
        $compiler->raw(')');
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
                if (!$value instanceof ConstantExpression || !preg_match('/^[^&<>"\'\x00-\x1F\x7F-\xFF]*$/', self::classes($value))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Whether joining the parts with spaces gives the string of their CssClassList, which skips empty parts and repeated classes.
     */
    private static function joinsLikeAClassList(Node $parts): bool
    {
        $seen = [];
        foreach ($parts as $part) {
            $branches = $part instanceof ConditionalTernary ? [$part->getNode('left'), $part->getNode('right')] : [$part];
            $classes = [];
            foreach ($branches as $branch) {
                $value = $branch->getAttribute('value');
                if ([] === $value || [] !== array_intersect_key($value, $seen)) {
                    return false;
                }
                $classes += $value;
            }
            $seen += $classes;
        }

        return true;
    }

    private static function toString(Node $part): AbstractExpression
    {
        if (!$part instanceof ConditionalTernary) {
            return new ConstantExpression(self::classes($part), $part->getTemplateLine());
        }

        $left = new ConstantExpression(self::classes($part->getNode('left')), $part->getTemplateLine());
        $right = new ConstantExpression(self::classes($part->getNode('right')), $part->getTemplateLine());

        return new ConditionalTernary($part->getNode('test'), $left, $right, $part->getTemplateLine());
    }

    private static function classes(Node $value): string
    {
        return implode(' ', array_keys($value->getAttribute('value')));
    }
}
