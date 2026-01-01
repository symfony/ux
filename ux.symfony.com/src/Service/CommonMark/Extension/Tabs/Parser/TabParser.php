<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\CommonMark\Extension\Tabs\Parser;

use App\Service\CommonMark\Extension\Tabs\Node\Tab;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

final class TabParser extends AbstractBlockContinueParser
{
    private Tab $block;
    private bool $finished = false;

    public function __construct(string $title)
    {
        $this->block = new Tab($title);
    }

    public static function createBlockStartParser(): BlockStartParserInterface
    {
        return new class implements BlockStartParserInterface {
            public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
            {
                if ($cursor->isIndented()) {
                    return BlockStart::none();
                }

                if (null === $start = $cursor->match('/^:: tab .+/')) {
                    return BlockStart::none();
                }

                return BlockStart::of(new TabParser(
                    trim(substr($start, 7))
                ))->at($cursor);
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
        return !$childBlock instanceof Tab;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        if ($cursor->isBlank()) {
            return BlockContinue::at($cursor);
        }

        $remaining = $cursor->getRemainder();

        if (str_starts_with($remaining, ':: tab ') || ':::' == $remaining) {
            return BlockContinue::none();
        }

        return BlockContinue::at($cursor);
    }
}
