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

use App\Service\CommonMark\Extension\Tabs\Node\Tabs;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

final class TabsParser extends AbstractBlockContinueParser
{
    private readonly Tabs $tabs;
    private bool $finished = false;

    public function __construct()
    {
        $this->tabs = new Tabs();
    }

    public static function createBlockStartParser(): BlockStartParserInterface
    {
        return new class implements BlockStartParserInterface {
            public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
            {
                if ($cursor->isIndented()) {
                    return BlockStart::none();
                }

                if (null === $cursor->match('/^::: tabs/')) {
                    return BlockStart::none();
                }

                return BlockStart::of(new TabsParser())->at($cursor);
            }
        };
    }

    public function getBlock(): AbstractBlock
    {
        return $this->tabs;
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
        if ($this->finished) {
            return BlockContinue::none();
        }

        if ($cursor->isBlank()) {
            return BlockContinue::at($cursor);
        }

        if (null !== $cursor->match('/^:::$/')) {
            $this->finished = true;
        }

        return BlockContinue::at($cursor);
    }
}
