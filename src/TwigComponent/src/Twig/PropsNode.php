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
use Twig\Node\Node;

/**
 * @author Matheo Daninos <matheo.daninos@gmail.com>
 *
 * @internal
 */
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
                ->write('$context[\'attributes\'] = $context[\'attributes\']->remove(\''.$name.'\');')
                ->write('if (!isset($context[\''.$name.'\'])) {');

            if (!$this->hasNode($name)) {
                $compiler
                    ->write('throw new \Twig\Error\RuntimeError("'.$name.' should be defined for component '.$this->getTemplateName().'");')
                    ->write('}');

                continue;
            }

            $compiler
                ->write('$context[\''.$name.'\'] = ')
                ->subcompile($this->getNode($name))
                ->raw(";\n")
                ->write('}');
        }

        $compiler
            ->write('$attributesKeys = array_keys($context[\'attributes\']->all());')
            ->raw("\n")
            ->write('foreach ($context as $key => $value) {')
            ->raw("\n")
            ->write('if (in_array($key, $attributesKeys) && !in_array($key, $propsNames)) {')
            ->raw("\n")
            ->raw('unset($context[$key]);')
            ->raw("\n")
            ->write('}')
            ->write('}')
        ;

        // overwrite the context value if a props with a similar name and a default value exist
        if ($this->hasNode($name)) {
            $compiler
                ->write('if (isset($context[\'__context\'][\''.$name.'\'])) {')
                ->raw("\n")
                ->write('$contextValue = $context[\'__context\'][\''.$name.'\'];')
                ->raw("\n")
                ->write('$propsValue = $context[\''.$name.'\'];')
                ->raw("\n")
                ->write('if ($contextValue === $propsValue) {')
                ->raw("\n")
                ->write('$context[\''.$name.'\'] = ')
                ->subcompile($this->getNode($name))
                ->raw(";\n")
                ->write('}')
                ->raw("\n")
                ->write('}')
            ;
        }
    }
}
