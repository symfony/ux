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

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Markdown\Extension\CodePreview\Node\CodePreview;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\Node\Tab;
use Symfony\UX\Toolkit\Markdown\Extension\Tabs\Node\Tabs;
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
        $exampleFile = Path::join($recipe->absolutePath, 'examples', $exampleName.'.html.twig');
        if (!is_file($exampleFile)) {
            throw new \InvalidArgumentException(\sprintf('Example "%s" does not exist for recipe "%s".', $exampleName, $recipe->name));
        }

        $code = trim((string) file_get_contents($exampleFile));

        $this->tabs = new Tabs();

        $previewTab = new Tab('Preview');
        $previewTab->appendChild(new CodePreview($code, $options));
        $this->tabs->appendChild($previewTab);

        $codeTab = new Tab('Code');
        $fencedCode = new FencedCode(3, '`', 0);
        $fencedCode->setInfo('twig');
        $fencedCode->setLiteral($code);
        $codeTab->appendChild($fencedCode);
        $this->tabs->appendChild($codeTab);
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

                $remainder = trim($cursor->getRemainder());
                $cursor->advanceToEnd();

                // Example names can contain spaces ("Custom Method"); options are an optional trailing JSON object.
                $name = $remainder;
                $options = [];
                if (preg_match('/^(.*?)\s*(\{.*\})$/s', $remainder, $matches) && \is_array($decoded = json_decode($matches[2], true))) {
                    $name = rtrim($matches[1]);
                    $options = $decoded;
                }

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
