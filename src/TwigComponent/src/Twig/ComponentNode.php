<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Twig;

use Twig\Compiler;
use Twig\Extension\CoreExtension;
use Twig\Node\EmbedNode;
use Twig\Node\Expression\AbstractExpression;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ComponentNode extends EmbedNode
{
    public function __construct(string $component, string $template, int $index, AbstractExpression $variables, bool $only, int $lineno, string $tag)
    {
        parent::__construct($template, $index, $variables, $only, false, $lineno, $tag);

        $this->setAttribute('component', $component);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        // since twig/twig 3.9.0: Using the internal "twig_to_array" function is deprecated.
        if (method_exists(CoreExtension::class, 'toArray')) {
            $twig_to_array = 'Twig\Extension\CoreExtension::toArray';
        } else {
            $twig_to_array = 'twig_to_array';
        }

        $compiler
            ->raw('$props = $this->extensions[')
            ->string(ComponentExtension::class)
            ->raw(']->embeddedContext(')
            ->string($this->getAttribute('component'))
            ->raw(', ')
            ->raw($twig_to_array)
            ->raw('(')
            ->subcompile($this->getNode('variables'))
            ->raw('), ')
            ->raw($this->getAttribute('only') ? '[]' : '$context')
            ->raw(");\n")
        ;

        $this->addGetTemplate($compiler);

        $compiler->raw('->display($props);');
        $compiler->raw("\n");
    }
}
