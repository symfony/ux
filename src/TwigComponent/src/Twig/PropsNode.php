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
    public function __construct(array $propsNames, array $values, $lineno = 0, string $tag = null)
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
                ->write('$propsNames[] = \''.$name.'\';')
                ->write('$context[\'attributes\'] = $context[\'attributes\']->remove(\''.$name.'\');')
                ->write('if (!isset($context[\'__props\'][\''.$name.'\'])) {');

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
    }
}
