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

use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 *
 * @internal
 */
class SlotTokenParser extends AbstractTokenParser
{
    public function parse(Token $token)
    {
        $parent = $this->parser->getExpressionParser()->parseExpression();

        $name = $this->slotName($parent);
        $variables = $this->parseArguments();

        $slot = $this->parser->subparse([$this, 'decideBlockEnd'], true);

        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new SlotNode($name, $slot, $variables, $token->getLine());
    }

    protected function parseArguments(): ?AbstractExpression
    {
        $stream = $this->parser->getStream();

        $variables = null;
        if ($stream->nextIf(/* Token::NAME_TYPE */ 5, 'with')) {
            $variables = $this->parser->getExpressionParser()->parseExpression();
        }

        $stream->expect(/* Token::BLOCK_END_TYPE */ 3);

        return $variables;
    }

    public function parseSlotName(): string
    {
        $stream = $this->parser->getStream();

        if (5 != /* Token::NAME_TYPE */ $this->parser->getCurrentToken()->getType()) {
            throw new \Exception('First token must be a name type');
        }

        return $stream->next()->getValue();
    }

    public function decideBlockEnd(Token $token): bool
    {
        return $token->test('endslot');
    }

    public function getTag(): string
    {
        return 'slot';
    }

    private function slotName(AbstractExpression $expression): string
    {
        if ($expression instanceof ConstantExpression) { // using {% component 'name' %}
            return $expression->getAttribute('value');
        }

        if ($expression instanceof NameExpression) { // using {% component name %}
            return $expression->getAttribute('name');
        }

        throw new \LogicException('Could not parse twig component name.');
    }
}
