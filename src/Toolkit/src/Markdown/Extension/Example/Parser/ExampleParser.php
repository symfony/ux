<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\Example\Parser;

use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use Symfony\UX\Toolkit\Markdown\Extension\Example\ExampleResolver;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\Node\Tabs;
use Symfony\UX\Toolkit\Markdown\PreviewTabsBuilder;
use Symfony\UX\Toolkit\Recipe\Recipe;

/**
 * Parses `::: example <Name> {json}` (a single-line leaf, no closing `:::`) and builds, directly in the AST,
 * a `Tabs(Preview: CodePreview, Code: FencedCode)` from the recipe's `examples/<Name>.html.twig`.
 *
 * The example code is put into the nodes verbatim (never re-serialized to markdown), so a snippet containing
 * ``` fences or `:::` colons cannot break the surrounding block.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class ExampleParser extends AbstractBlockContinueParser
{
    private readonly Tabs $tabs;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(Recipe $recipe, string $exampleName, array $options)
    {
        $this->tabs = PreviewTabsBuilder::build(ExampleResolver::readCode($recipe, $exampleName), $options);
    }

    public static function createBlockStartParser(Recipe $recipe): BlockStartParserInterface
    {
        return new class($recipe) implements BlockStartParserInterface {
            public function __construct(
                private readonly Recipe $recipe,
            ) {
            }

            public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
            {
                if ($cursor->isIndented()) {
                    return BlockStart::none();
                }

                if (null === $cursor->match('/^::: example\s+/')) {
                    return BlockStart::none();
                }

                $remainder = $cursor->getRemainder();
                $cursor->advanceToEnd();

                [$name, $options] = ExampleResolver::parse($remainder);
                if ('' === $name) {
                    return BlockStart::none();
                }

                return BlockStart::of(new ExampleParser($this->recipe, $name, $options))->at($cursor);
            }
        };
    }

    public function getBlock(): AbstractBlock
    {
        return $this->tabs;
    }

    public function isContainer(): bool
    {
        return false;
    }

    public function canContain(AbstractBlock $childBlock): bool
    {
        return false;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        return BlockContinue::none();
    }
}
