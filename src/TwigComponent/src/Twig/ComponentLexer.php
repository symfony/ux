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

use Twig\Environment;
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
    private Environment $env;
    private array $options;
    private bool $isConstructed = false;

    public function __construct(Environment $env, array $options = [])
    {
        $this->env = $env;
        $this->options = $options;
    }

    public function tokenize(Source $source): TokenStream
    {
        // odd behavior is because the parent constructor causes the extension set
        // to be initialized earlier than it normally would be.
        // https://github.com/symfony/ux/issues/835
        if (!$this->isConstructed) {
            parent::__construct($this->env, $this->options);

            $this->isConstructed = true;
        }

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
