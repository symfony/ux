<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\Example;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use Symfony\UX\Toolkit\Assert;
use Symfony\UX\Toolkit\Markdown\Extension\Example\Parser\ExampleParser;
use Symfony\UX\Toolkit\Recipe\Recipe;

/**
 * The recipe-aware `::: example <Name>` sugar: a single-line leaf directive that expands, in the AST,
 * to a Tabs block with a live Preview and the source Code, using the recipe's `examples/<Name>.html.twig`.
 *
 * It is bound to a recipe, so a host registers it per render (the recipe provides the examples to resolve).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class ExampleExtension implements ExtensionInterface
{
    public function __construct(
        private readonly Recipe $recipe,
    ) {
        Assert::commonMarkAvailable();
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addBlockStartParser(ExampleParser::createBlockStartParser($this->recipe), 110);
    }
}
