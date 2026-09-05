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
use Twig\Node\Node;

/**
 * @author Matheo Daninos <matheo.daninos@gmail.com>
 *
 * @internal
 */
#[YieldReady]
class PropsNode extends Node
{
    /**
     * @param array<string, string> $documentations The `## ...` documentation of each documented prop, keyed by name
     */
    public function __construct(array $propsNames, array $values, array $documentations = [], $lineno = 0)
    {
        parent::__construct($values, ['names' => $propsNames, 'documentations' => $documentations], $lineno);
    }

    /**
     * The `## ...` documentation attached to the given prop, or null when it is undocumented.
     */
    public function getPropDocumentation(string $name): ?string
    {
        return $this->getAttribute('documentations')[$name] ?? null;
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        if (!$propsNames = $this->getAttribute('names')) {
            $compiler->write('$propsNames = [];');

            return;
        }

        $compiler
            ->write('$propsNames = [\''.implode("', '", $propsNames).'\'];')
            ->raw("\n")
            ->write('$context[\'attributes\'] = $context[\'attributes\']->without(...$propsNames);')
            ->raw("\n")
        ;

        foreach ($this->getAttribute('names') as $name) {
            $compiler
                ->write('if (array_key_exists(\''.$name.'\', $context[\'__props\'] ?? [])) {')
                ->raw("\n")
                ->indent()
                ->write('$componentClass = isset($context[\'this\']) ? get_debug_type($context[\'this\']) : "";')
                ->raw("\n")
                ->write('throw new \Twig\Error\RuntimeError(\'Cannot define prop "'.$name.'" in template "'.$this->getTemplateName().'". Property already defined in component class "\'.$componentClass.\'".\');')
                ->raw("\n")
                ->outdent()
                ->write('}')
                ->raw("\n")
            ;

            $compiler->write('if (!array_key_exists(\''.$name.'\', $context)) {');

            if (!$this->hasNode($name)) {
                $compiler
                    ->write("\n")
                    ->indent()
                    ->write('throw new \Twig\Error\RuntimeError(\'Prop "'.$name.'" should be defined.\');')
                    ->write("\n")
                    ->outdent()
                    ->write('}')
                    ->write("\n");

                continue;
            }

            $compiler
                ->indent()
                ->write('$context[\''.$name.'\'] = ')
                ->subcompile($this->getNode($name))
                ->raw(";\n")
                ->outdent()
                ->write('}')
                ->write("\n")
            ;

            // overwrite the context value if a props with a similar name and a default value exist
            if ($this->hasNode($name)) {
                $compiler
                    ->write('if (array_key_exists(\''.$name.'\', $context[\'__context\'] ?? [])) {')
                    ->raw("\n")
                    ->indent()
                    ->write('$context[\''.$name.'\'] = ')
                    ->subcompile($this->getNode($name))
                    ->raw(";\n")
                    ->outdent()
                    ->write('}')
                    ->raw("\n")
                ;
            }
        }

        $compiler
            ->write('foreach ($context[\'attributes\']->all() as $key => $value) {')
            ->raw("\n")
            ->indent()
            ->raw('unset($context[$key]);')
            ->raw("\n")
            ->outdent()
            ->write('}')
            ->raw("\n")
        ;
    }
}
