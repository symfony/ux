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
use Twig\Environment;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 *
 * @internal
 */
final class TwigComponentTokenParser extends AbstractTokenParser
{
    /** @var ComponentFactory|callable():ComponentFactory */
    private $factory;

    private Environment $environment;
    public function __construct(
        $factory,
        Environment $environment
    ) {
        $this->factory = $factory;
        $this->environment = $environment;
    }

    public function parse(Token $token): Node
    {
        $parent = $this->parser->getExpressionParser()->parseExpression();
        $name = $this->componentName($parent);
        [$variables, $only] = $this->parseArguments();
        $slot = $this->parser->subparse([$this, 'decideBlockEnd'], true);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new TwigComponentNode($name, $slot, $variables, $token->getLine(), $this->factory, $this->environment);
    }

    public function getTag(): string
    {
        return 'twig_component';
    }

    public function decideBlockEnd(Token $token): bool
    {
        return $token->test('end_twig_component');
    }

    private function componentName(AbstractExpression $expression): string
    {
        if ($expression instanceof ConstantExpression) { // using {% component 'name' %}
            return $expression->getAttribute('value');
        }

        if ($expression instanceof NameExpression) { // using {% component name %}
            return $expression->getAttribute('name');
        }

        throw new \LogicException('Could not parse twig component name.');
    }

    private function parseArguments(): array
    {
        $stream = $this->parser->getStream();

        $variables = null;

        if ($stream->nextIf(Token::NAME_TYPE, 'with')) {
            $variables = $this->parser->getExpressionParser()->parseExpression();
        }

        $only = false;

        if ($stream->nextIf(Token::NAME_TYPE, 'only')) {
            $only = true;
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return [$variables, $only];
    }
}