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

use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Twig\Compiler;
use Twig\Environment;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\IncludeNode;
use Twig\Node\Node;

/**
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 *
 * @internal
 */
class TwigComponentNode extends IncludeNode
{
    private Environment $environment;

    /**
     * @param callable():ComponentFactory $factory
     */
    public function __construct(string $componentName, Node $slot, ?AbstractExpression $variables, int $lineno, callable $factory, Environment $environment)
    {
        parent::__construct(new ConstantExpression('not_used', $lineno), $variables, false, false, $lineno, null);
        $this->setAttribute('componentName', $componentName);
        $this->setAttribute('componentMetadata', $factory()->metadataFor($componentName));
        $this->setNode('slot', $slot);
        $this->environment = $environment;
    }
    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        $template = $compiler->getVarName();

        $compiler->write(sprintf("$%s = ", $template));

        $this->addGetTemplate($compiler);

        $compiler
            ->write(sprintf("if ($%s) {\n", $template))
            ->write('$slotsStack = $slotsStack ?? [];' . PHP_EOL)
            ->write('$slotsStack[] = $slots ?? [];' . PHP_EOL)
            ->write('$slots = [];' . PHP_EOL)
        ;

        if ($this->getAttribute('componentMetadata') instanceof ComponentMetadata) {
            $this->addComponentProps($compiler);
        }

        $compiler
            ->write("ob_start();"  . PHP_EOL)
            ->subcompile($this->getNode('slot'))
            ->write('$slot = ob_get_clean();' . PHP_EOL)
            ->write(sprintf('$%s->display(', $template));

        $this->addTemplateArguments($compiler);

        $compiler
            ->raw(");\n")
            ->write('$slots = array_pop($slotsStack);' . PHP_EOL)
            ->write("}\n")
        ;
    }

    protected function addGetTemplate(Compiler $compiler)
    {
        $compiler
            ->raw('$this->loadTemplate(' . PHP_EOL)
            ->indent(1)
            ->write('')
            ->repr($this->getTemplatePath())
            ->raw(', ' . PHP_EOL)
            ->write('')
            ->repr($this->getTemplatePath())
            ->raw(', ' . PHP_EOL)
            ->write('')
            ->repr($this->getTemplateLine())
            ->indent(-1)
            ->raw(PHP_EOL . ');' . PHP_EOL . PHP_EOL);
    }

    protected function addTemplateArguments(Compiler $compiler)
    {
        $compiler
            ->indent(1)
            ->write("\n")
            ->write("array_merge(\n")
            ->write('$slots,' . PHP_EOL)
        ;

        if ($this->getAttribute('componentMetadata') instanceof ComponentMetadata) {
            $compiler->write('$props,' . PHP_EOL);
        }

        $compiler
            ->write('$context,[')
            ->write("'slot' => new  " . ComponentSlot::class . " (\$slot),\n")
            ->write("'attributes' => new " . AttributeBag::class . "(");

        if ($this->hasNode('variables')) {
            $compiler->subcompile($this->getNode('variables'));
        } else {
            $compiler->raw('[]');
        }

        $compiler->write(")\n")
            ->indent(-1)
            ->write("],");

        if ($this->hasNode('variables')) {
            $compiler->subcompile($this->getNode('variables'));
        } else {
            $compiler->raw('[]');
        }

        $compiler->write(")\n");
    }

    private function getTemplatePath(): string
    {
        $name = $this->getAttribute('componentName');

        $loader = $this->environment->getLoader();
        $componentPath = rtrim(str_replace('.', '/', $name));

        /** @var ComponentMetadata $componentMetadata */
        if (($componentMetadata = $this->getAttribute('componentMetadata')) !== null) {
            return $componentMetadata->getTemplate();
        }

        if ($loader->exists($componentPath)) {
            return $componentPath;
        }

        if ($loader->exists($componentPath . '.html.twig')) {
            return $componentPath . '.html.twig';
        }

        if ($loader->exists('components/' . $componentPath)) {
            return 'components/' . $componentPath;
        }

        if ($loader->exists('/components/' . $componentPath . '.html.twig')) {
            return '/components/' . $componentPath . '.html.twig';
        }

        throw new \LogicException("No template found for: {$name}");
    }

    private function addComponentProps(Compiler $compiler)
    {
        $compiler
            ->raw('$props = $this->extensions[')
            ->string(ComponentExtension::class)
            ->raw(']->embeddedContext(')
            ->string($this->getAttribute('componentName'))
            ->raw(', ')
            ->raw('twig_to_array(')
            ->subcompile($this->getNode('variables'))
            ->raw('), ')
            ->raw('$context')
            ->raw(");\n")
        ;
    }
}