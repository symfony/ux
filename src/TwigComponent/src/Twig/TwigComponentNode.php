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

use Symfony\UX\TwigComponent\ComponentMetadata;
use Twig\Compiler;
use Twig\Node\EmbedNode;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;

/**
 * thanks to @giorgiopogliani!
 * This file is inspired by: https://github.com/giorgiopogliani/twig-components.
 *
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 *
 * @internal
 */
class TwigComponentNode extends EmbedNode
{
    public function __construct(string $componentName, string $template, ?AbstractExpression $variables, int $lineno, $index, $tag, bool $only, Node $slot, ?ComponentMetadata $componentMetadata)
    {
        parent::__construct($template, $index, $variables, $only, false, $lineno, $tag);

        $this->setAttribute('component', $componentName);
        $this->setAttribute('componentMetadata', $componentMetadata);

        $this->setNode('slot', $slot);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        $template = $compiler->getVarName();
        $compiler->write(sprintf('$%s = ', $template));
        $this->addGetTemplate($compiler);

        $compiler
            ->raw(';')
            ->write(sprintf("if ($%s) {\n", $template))
            ->write('$slotsStack = $slotsStack ?? [];'.\PHP_EOL)
            ->write('$slotsStack[] = $slots ?? [];'.\PHP_EOL)
            ->write('$slots = [];'.\PHP_EOL)
        ;

        if ($this->getAttribute('componentMetadata') instanceof ComponentMetadata) {
            $this->addComponentProps($compiler);
        }

        $compiler
            ->write('ob_start();'.\PHP_EOL)
            ->subcompile($this->getNode('slot'))
            ->write('$slot = ob_get_clean();'.\PHP_EOL)
        ;

        $compiler->raw(sprintf('$%s->display(', $template));

        $this->addTemplateArguments($compiler);
        $compiler->raw(");\n");
        $compiler->write("}\n");
    }

    protected function addTemplateArguments(Compiler $compiler)
    {
        $compiler
            ->indent(1)
            ->write("\n")
            ->write("array_merge(\n")
            ->write('$slots,'.\PHP_EOL)
        ;

        if ($this->getAttribute('componentMetadata') instanceof ComponentMetadata) {
            $compiler->write('$props,'.\PHP_EOL);
        }

        $compiler
            ->write('$context,[')
            ->write("'slot' => new  ".ComponentSlot::class." (\$slot),\n")
            ->write("'attributes' => new ".AttributeBag::class.'(');

        if ($this->hasNode('variables')) {
            $compiler->subcompile($this->getNode('variables'));
        } else {
            $compiler->raw('[]');
        }

        $compiler->write(")\n")
            ->indent(-1)
            ->write('],');

        if ($this->hasNode('variables')) {
            $compiler->subcompile($this->getNode('variables'));
        } else {
            $compiler->raw('[]');
        }

        $compiler->write(")\n");
    }

    private function addComponentProps(Compiler $compiler)
    {
        $compiler
            ->raw('$props = $this->extensions[')
            ->string(ComponentExtension::class)
            ->raw(']->embeddedContext(')
            ->string($this->getAttribute('component'))
            ->raw(', ')
        ;

        if ($this->hasNode('variables')) {
            $compiler
                ->raw('twig_to_array(')
                ->subcompile($this->getNode('variables'))
                ->raw('), ')
            ;
        } else {
            $compiler->raw('[], ');
        }

        $compiler
            ->raw('$context')
            ->raw(");\n")
        ;
    }
}
