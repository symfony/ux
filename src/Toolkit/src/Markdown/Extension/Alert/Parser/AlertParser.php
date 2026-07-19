<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\Alert\Parser;

use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;
use Symfony\UX\Toolkit\Markdown\Extension\Alert\Node\Alert;

final class AlertParser extends AbstractBlockContinueParser
{
    private Alert $block;

    public function __construct(string $type)
    {
        $this->block = new Alert($type);
    }

    public static function createBlockStartParser(): BlockStartParserInterface
    {
        return new class implements BlockStartParserInterface {
            public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
            {
                if ($cursor->isIndented()) {
                    return BlockStart::none();
                }

                if (null === $match = $cursor->match('/^>\s*\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]\s*$/i')) {
                    return BlockStart::none();
                }

                preg_match('/\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]/i', $match, $captures);

                return BlockStart::of(new AlertParser(strtolower($captures[1])))->at($cursor);
            }
        };
    }

    public function getBlock(): AbstractBlock
    {
        return $this->block;
    }

    public function isContainer(): bool
    {
        return true;
    }

    public function canContain(AbstractBlock $childBlock): bool
    {
        return true;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        if ($cursor->isBlank()) {
            return BlockContinue::none();
        }

        $cursor->advanceToNextNonSpaceOrTab();
        if ('>' !== $cursor->getCurrentCharacter()) {
            return BlockContinue::none();
        }

        $cursor->advance();
        if (' ' === $cursor->getCurrentCharacter()) {
            $cursor->advance();
        }

        return BlockContinue::at($cursor);
    }
}
