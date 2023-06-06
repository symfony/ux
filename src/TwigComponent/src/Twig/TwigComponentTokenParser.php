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
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\ModuleNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * thanks to @giorgiopogliani!
 * This file is inspired by: https://github.com/giorgiopogliani/twig-components.
 *
 * @author Mathéo Daninos <matheo.daninos@gmail.com>
 *
 * @internal
 */
final class TwigComponentTokenParser extends AbstractTokenParser
{
    /** @var ComponentFactory|callable():ComponentFactory */
    private $factory;

    private Environment $environment;

    /**
     * @param callable():ComponentFactory $factory
     */
    public function __construct(
        callable $factory,
        Environment $environment
    ) {
        $this->factory = $factory;
        $this->environment = $environment;
    }

    public function parse(Token $token): Node
    {
        $stream = $this->parser->getStream();
        $parent = $this->parser->getExpressionParser()->parseExpression();
        $componentName = $this->componentName($parent);
        $componentMetadata = $this->factory()->metadataForTwigComponent($componentName);

        [$variables, $only] = $this->parseArguments();

        if (null === $variables) {
            $variables = new ArrayExpression([], $parent->getTemplateLine());
        }

        $parentToken = new Token(Token::STRING_TYPE, $this->getTemplatePath($componentName), $token->getLine());
        $fakeParentToken = new Token(Token::STRING_TYPE, '__parent__', $token->getLine());

        // inject a fake parent to make the parent() function work
        $stream->injectTokens([
            new Token(Token::BLOCK_START_TYPE, '', $token->getLine()),
            new Token(Token::NAME_TYPE, 'extends', $token->getLine()),
            $parentToken,
            new Token(Token::BLOCK_END_TYPE, '', $token->getLine()),
        ]);

        $module = $this->parser->parse($stream, fn (Token $token) => $token->test("end_{$this->getTag()}"), true);

        $slot = $this->getSlotFromBlockContent($module);

        // override the parent with the correct one
        if ($fakeParentToken === $parentToken) {
            $module->setNode('parent', $parent);
        }

        $this->parser->embedTemplate($module);

        $stream->expect(Token::BLOCK_END_TYPE);

        return new TwigComponentNode(
            $componentName,
            $module->getTemplateName(),
            $variables, $token->getLine(),
            $module->getAttribute('index'),
            $this->getTag(),
            $only,
            $slot,
            $componentMetadata
        );
    }

    public function getTag(): string
    {
        return 'twig_component';
    }

    private function componentName(AbstractExpression $expression): string
    {
        if ($expression instanceof ConstantExpression) { // using {% component 'name' %}
            return $expression->getAttribute('value');
        }

        if ($expression instanceof NameExpression) { // using {% component name %}
            return $expression->getAttribute('name');
        }

        throw new \LogicException('Could not parse component name.');
    }

    private function factory(): ComponentFactory
    {
        if (\is_callable($this->factory)) {
            $this->factory = ($this->factory)();
        }

        return $this->factory;
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

    private function getTemplatePath(string $name): string
    {
        $loader = $this->environment->getLoader();
        $componentPath = rtrim(str_replace('.', '/', $name));

        if (($componentMetadata = $this->factory->metadataForTwigComponent($name)) !== null) {
            return $componentMetadata->getTemplate();
        }

        if ($loader->exists($componentPath)) {
            return $componentPath;
        }

        if ($loader->exists($componentPath.'.html.twig')) {
            return $componentPath.'.html.twig';
        }

        if ($loader->exists('components/'.$componentPath)) {
            return 'components/'.$componentPath;
        }

        if ($loader->exists('/components/'.$componentPath.'.html.twig')) {
            return '/components/'.$componentPath.'.html.twig';
        }

        throw new \LogicException("No template found for: {$name}");
    }

    private function getSlotFromBlockContent(ModuleNode $module): Node
    {
        if ($module->getNode('blocks')->hasNode('content')) {
            return $module->getNode('blocks')->getNode('content')->getNode(0)->getNode('body');
        }

        return new Node();
    }
}
