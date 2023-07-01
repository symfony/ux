<?php

namespace Symfony\UX\TwigComponent\Twig;

use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class PropsTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $parser = $this->parser;
        $stream = $parser->getStream();

        $names = [];
        $values = [];
        while (!$stream->nextIf(Token::BLOCK_END_TYPE)) {
            $name = $stream->expect(\Twig\Token::NAME_TYPE)->getValue();

            if ($stream->nextIf(Token::OPERATOR_TYPE, '=')) {
                $values[$name] = $parser->getExpressionParser()->parseExpression();
            }

            $names[] = $name;

            if (!$stream->nextIf(Token::PUNCTUATION_TYPE)) {
                break;
            }
        }

        $stream->expect(\Twig\Token::BLOCK_END_TYPE);

        return new PropsNode($names, $values, $token->getLine(), $token->getValue());
    }

    public function getTag(): string
    {
        return 'props';
    }
}
