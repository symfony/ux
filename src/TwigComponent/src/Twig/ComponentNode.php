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

use Symfony\UX\TwigComponent\BlockStack;
use Twig\Attribute\YieldReady;
use Twig\Compiler;
use Twig\Environment;
use Twig\Extension\CoreExtension;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
#[YieldReady]
final class ComponentNode extends Node implements NodeOutputInterface
{
    public function __construct(string $component, string $embeddedTemplateName, int $embeddedTemplateIndex, ?AbstractExpression $props, bool $only, int $lineno, string $tag)
    {
        $nodes = [];
        if (null !== $props) {
            $nodes['props'] = $props;
        }

        parent::__construct($nodes, [], $lineno, $tag);

        $this->setAttribute('only', $only);
        $this->setAttribute('embedded_template', $embeddedTemplateName);
        $this->setAttribute('embedded_index', $embeddedTemplateIndex);
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

        /*
         * Block 1) PreCreateForRender handling
         *
         * We call code to trigger the PreCreateForRender event. If the event returns
         * a string, we return that string and skip the rest of the rendering process.
         */
        $compiler
            ->write('$preRendered = $this->extensions[')
            ->string(ComponentExtension::class)
            ->raw(']->extensionPreCreateForRender(')
            ->string($this->getAttribute('component'))
            ->raw(', ')
            ->raw($twig_to_array)
            ->raw('(');
        $this->writeProps($compiler)
            ->raw(')')
            ->raw(");\n");

        $compiler
            ->write('if (null !== $preRendered) {')
            ->raw("\n")
            ->indent();
        if (method_exists(Environment::class, 'useYield')) {
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
            ->write('$preRenderEvent = $this->extensions[')
            ->string(ComponentExtension::class)
            ->raw(']->startEmbeddedComponentRender(')
            ->string($this->getAttribute('component'))
            ->raw(', ')
            ->raw($twig_to_array)
            ->raw('(');
        $this->writeProps($compiler)
            ->raw('), ')
            ->raw($this->getAttribute('only') ? '[]' : '$context')
            ->raw(', ')
            ->string(TemplateNameParser::parse($this->getAttribute('embedded_template')))
            ->raw(', ')
            ->raw($this->getAttribute('embedded_index'))
            ->raw(");\n");
        $compiler
            ->write('$embeddedContext = $preRenderEvent->getVariables();')
            ->raw("\n")
            // Add __parent__ to the embedded context: this is used in its extends
            // Note: PreRenderEvent::getTemplateIndex() is not used here. This is
            // only used during "normal" {{ component() }} rendering, which allows
            // you to target rendering a specific "embedded template" that originally
            // came from a {% component %} tag. This is used by LiveComponents to
            // allow an "embedded component" syntax live component to be re-rendered.
            // In this case, we are obviously rendering an entire template, which
            // happens to contain a {% component %} tag. So we don't need to worry
            // about trying to allow a specific embedded template to be targeted.
            ->write('$embeddedContext["__parent__"] = $preRenderEvent->getTemplate();')
            ->raw("\n");

        /*
         * Block 3) Add & update the block stack
         *
         * We add the outerBlock to the context if it doesn't exist yet.
         * Then add them to the block stack and get the converted embedded blocks.
         */
        $compiler->write('if (!isset($embeddedContext["outerBlocks"])) {')
            ->raw("\n")
            ->indent()
            ->write(\sprintf('$embeddedContext["outerBlocks"] = new \%s();', BlockStack::class))
            ->raw("\n")
            ->outdent()
            ->write('}')
            ->raw("\n");

        $compiler->write('$embeddedBlocks = $embeddedContext[')
            ->string('outerBlocks')
            ->raw(']->convert($blocks, ')
            ->raw($this->getAttribute('embedded_index'))
            ->raw(");\n");

        /*
         * Block 4) Render the component template
         *
         * This will actually render the child component template.
         */
        if (method_exists(Environment::class, 'useYield') && $compiler->getEnvironment()->useYield()) {
            $compiler
                ->write('yield from ');
        }
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

        if (method_exists(Environment::class, 'useYield') && $compiler->getEnvironment()->useYield()) {
            $compiler->raw('->unwrap()->yield(');
        } else {
            $compiler->raw('->display(');
        }
        $compiler
            ->raw('$embeddedContext, $embeddedBlocks')
            ->raw(");\n");

        $compiler->write('$this->extensions[')
            ->string(ComponentExtension::class)
            ->raw(']->finishEmbeddedComponentRender()')
            ->raw(";\n")
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
