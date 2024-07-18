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
    public function __construct(array $propsNames, array $values, $lineno = 0, ?string $tag = null)
    {
        parent::__construct($values, ['names' => $propsNames], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler
            ->addDebugInfo($this)
            ->write('$propsNames = [];')
        ;

        if (!$this->getAttribute('names')) {
            return;
        }

        foreach ($this->getAttribute('names') as $name) {
            $compiler
                ->write('if (isset($context[\'__props\'][\''.$name.'\'])) {')
                ->raw("\n")
                ->write('$componentClass = isset($context[\'this\']) ? get_debug_type($context[\'this\']) : "";')
                ->raw("\n")
                ->write('throw new \Twig\Error\RuntimeError(\'Cannot define prop "'.$name.'" in template "'.$this->getTemplateName().'". Property already defined in component class "\'.$componentClass.\'".\');')
                ->raw("\n")
                ->write('}')
                ->raw("\n")
            ;

            $compiler
                ->write('$propsNames[] = \''.$name.'\';')
                ->write("\n")
                ->write('$context[\'attributes\'] = $context[\'attributes\']->remove(\''.$name.'\');')
                ->write("\n")
                ->write('if (!isset($context[\''.$name.'\'])) {');

            if (!$this->hasNode($name)) {
                $compiler
                    ->indent()
                    ->write('throw new \Twig\Error\RuntimeError("'.$name.' should be defined for component '.$this->getTemplateName().'.");')
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
                    ->write('if (isset($context[\'__context\'][\''.$name.'\'])) {')
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
            ->write('$attributesKeys = array_keys($context[\'attributes\']->all());')
            ->raw("\n")
            ->write('foreach ($context as $key => $value) {')
            ->raw("\n")
            ->indent()
            ->write('if (in_array($key, $attributesKeys) && !in_array($key, $propsNames)) {')
            ->raw("\n")
            ->indent()
            ->raw('unset($context[$key]);')
            ->raw("\n")
            ->outdent()
            ->write('}')
            ->raw("\n")
            ->outdent()
            ->write('}')
            ->raw("\n")
        ;
    }
}
