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
        foreach ($this->getAttribute('names') as $name) {
            $compiler
                ->addDebugInfo($this)
                ->write('if (!isset($context[\''.$name.'\'])) {')
            ;

            if (!$this->hasNode($name)) {
                $compiler
                    ->write('throw new \Exception("'.$name.' should be defined for component '.$this->getTemplateName().'");')
                    ->write('}')
                ;

                continue;
            }

            $compiler
                ->write('$context[\''.$name.'\'] = ')
                ->subcompile($this->getNode($name))
                ->raw(";\n")
                ->write('}')
            ;
        }
    }
}
