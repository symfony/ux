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

use Twig\Lexer;
use Twig\Source;
use Twig\TokenStream;

/**
 * @author Mathèo Daninos <matheo.daninos@gmail.com>
 *
 * @internal
 *
 * thanks to @giorgiopogliani for the inspiration on this lexer <3
 *
 * @see https://github.com/giorgiopogliani/twig-components
 */
class ComponentLexer extends Lexer
{
    public function tokenize(Source $source): TokenStream
    {
        $preLexer = new TwigPreLexer();
        $preparsed = $preLexer->preLexComponents($source->getCode());

        return parent::tokenize(
            new Source(
                $preparsed,
                $source->getName(),
                $source->getPath()
            )
        );
    }
}
