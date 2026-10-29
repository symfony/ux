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

use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Environment;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Expression\Variable\ContextVariable;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;
use Twig\Template;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
#[YieldReady]
final class ComponentNode extends Node implements NodeOutputInterface
{
    public function __construct(AbstractExpression $component, string $embeddedTemplateName, int $embeddedTemplateIndex, ?AbstractExpression $props, bool $only, int $lineno)
    {
        $nodes = ['component' => $component];
        if (null !== $props) {
            $nodes['props'] = $props;
        }

        parent::__construct($nodes, [], $lineno);

        $this->setAttribute('only', $only);
        $this->setAttribute('embedded_template', $embeddedTemplateName);
        $this->setAttribute('embedded_index', $embeddedTemplateIndex);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        // Twig 4 removed useYield() and only supports yield; the probe below must force it on.
        $isTwig4 = Environment::MAJOR_VERSION >= 4;
        $canYield = $isTwig4 || method_exists(Environment::class, 'useYield');
        $useYield = $isTwig4 || ($canYield && $compiler->getEnvironment()->useYield());

        $componentRuntime = $compiler->getVarName();
        $compiler
               ->write(\sprintf('$%s = $this->env->getRuntime(', $componentRuntime))
               ->string(ComponentRuntime::class)
               ->raw(");\n");

        $componentName = $compiler->getVarName();
        $componentExpression = $this->getNode('component');

        // NameExpression on Twig 3, ContextVariable on Twig 4
        if (($componentExpression instanceof NameExpression || $componentExpression instanceof ContextVariable) && !$componentExpression->hasExplicitParentheses()) {
            $compiler
                ->write(\sprintf('$%s = %s;', $componentName, var_export($componentExpression->getAttribute('name'), true)))
                ->raw("\n");
        } else {
            $componentNameValue = $compiler->getVarName();
            $compiler
                ->write(\sprintf('$%s = ', $componentNameValue))
            ;

            $compiler->subcompile($componentExpression);
            $compiler->raw(";\n");

            $compiler
                ->write(\sprintf('if (\\is_scalar($%s) || $%s instanceof \\Stringable) {', $componentNameValue, $componentNameValue))
                ->raw("\n")
                ->indent()
                ->write(\sprintf('$%s = (string) $%s;', $componentName, $componentNameValue))
                ->raw("\n")
                ->outdent()
                ->write("} else {\n")
                ->indent()
                ->write('throw new ')
                ->raw('\\'.SyntaxError::class)
                ->raw('(sprintf(')
                ->string('The component expression passed to "{%% component %%}" must evaluate to a component name (string/scalar/Stringable). Got "%s".')
                ->raw(', \\get_debug_type(')
                ->raw(\sprintf('$%s', $componentNameValue))
                ->raw(')), ')
                ->repr($this->getTemplateLine())
                ->raw(", \$this->getSourceContext());\n")
                ->outdent()
                ->write("}\n");
        }

        // Compile props once: Twig 3.24+ null-safe chains are stateful when compiled.
        $props = $compiler->getVarName();
        $compiler
            ->write(\sprintf('$%s = ', $props))
            ->raw('Twig\Extension\CoreExtension::toArray')
            ->raw('(');
        $this->writeProps($compiler)
            ->raw(");\n");

        /*
         * Block 1) PreCreateForRender handling
         *
         * We call code to trigger the PreCreateForRender event. If the event returns
         * a string, we return that string and skip the rest of the rendering process.
         */
        $compiler
            ->write(\sprintf('$preRendered = $%s->preRender(', $componentRuntime))
            ->raw(\sprintf('$%s, $%s', $componentName, $props))
            ->raw(");\n");

        $compiler
            ->write('if (null !== $preRendered) {')
            ->raw("\n")
            ->indent();
        // #[YieldReady]: emit yield whenever Twig understands it (hence $canYield, not $useYield).
        if ($canYield) {
            $compiler->write('yield $preRendered; ');
        } else {
            $compiler->write('echo $preRendered; ');
        }
        $compiler->raw("\n")
            ->outdent()
            ->write('} else {')
            ->raw("\n")
            ->indent();

        /*
         * Block 2) Create the component & return render info
         *
         * We call code that creates the component and dispatches the
         * PreRender event. The result $preRenderEvent variable holds
         * the final template, template index & variables.
         */
        $compiler
            ->write(\sprintf('$preRenderEvent = $%s->startEmbedComponent(', $componentRuntime))
            ->raw(\sprintf('$%s, $%s, ', $componentName, $props))
            ->raw($this->getAttribute('only') ? '[]' : '$context')
            ->raw(', ')
            ->string($this->getAttribute('embedded_template'))
            ->raw(', ')
            ->raw($this->getAttribute('embedded_index'))
            ->raw(");\n");

        // The variables already hold "__parent__" (used in the extends of the embedded template) and "outerBlocks".
        // Writing to them here would copy them, because the event holds them too.
        $compiler
            ->write('$embeddedContext = $preRenderEvent->getVariables();')
            ->raw("\n");

        /*
         * Block 3) Update the block stack
         *
         * Add the blocks to the block stack and get the converted embedded blocks.
         */
        $compiler->write('$embeddedBlocks = $embeddedContext["outerBlocks"]->convert($blocks, ')
            ->raw($this->getAttribute('embedded_index'))
            ->raw(", \$this, \$hostEmbeddedTemplateIndex);\n");

        /*
         * Block 4) Render the component template
         *
         * This will actually render the child component template.
         */
        if ($useYield) {
            $compiler->write('yield from ');
        }

        // Support for Twig ^3.21
        if (method_exists(Template::class, 'load')) {
            $compiler
               ->write('$this->load(')
               ->string($this->getAttribute('embedded_template'))
               ->raw(', ')
               ->repr($this->getTemplateLine())
               ->raw(', ')
               ->string($this->getAttribute('embedded_index'))
               ->raw(')');
        } else {
            $compiler
                ->write('$this->loadTemplate(')
                ->string($this->getAttribute('embedded_template'))
                ->raw(', ')
                ->repr($this->getTemplateName())
                ->raw(', ')
                ->repr($this->getTemplateLine())
                ->raw(', ')
                ->string($this->getAttribute('embedded_index'))
                ->raw(')');
        }

        if ($useYield) {
            $compiler->raw('->unwrap()->yield(');
        } else {
            $compiler->raw('->display(');
        }
        $compiler
            ->raw('$embeddedContext, $embeddedBlocks')
            ->raw(");\n");

        $compiler->write(\sprintf('$%s->finishEmbedComponent();', $componentRuntime))
            ->raw("\n")
        ;

        $compiler
            ->outdent()
            ->write('}')
            ->raw("\n")
        ;
    }

    private function writeProps(Compiler $compiler): Compiler
    {
        if ($this->hasNode('props')) {
            return $compiler->subcompile($this->getNode('props'));
        }

        return $compiler->raw('[]');
    }
}
