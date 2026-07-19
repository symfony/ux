<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\Popover\Parser;

use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;
use Symfony\UX\Toolkit\Markdown\Extension\Popover\Node\Popover;

/**
 * Parses the inline `(?)[description]` syntax into a {@see Popover} node.
 *
 * Used in the toolkit API Reference props table to move each prop description
 * behind a hoverable help button instead of a dedicated table column.
 */
final class PopoverParser implements InlineParserInterface
{
    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::string('(?)');
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        $state = $cursor->saveState();

        // The cursor is positioned at the start of "(?)"; consume it.
        $cursor->advanceBy($inlineContext->getFullMatchLength());

        // A "[description]" must immediately follow, otherwise this is not our token.
        $match = $cursor->match('/^\[(.+)\]/');
        if (null === $match) {
            $cursor->restoreState($state);

            return false;
        }

        // Strip the surrounding brackets (both are single-byte ASCII).
        $description = trim(substr($match, 1, -1));
        if ('' === $description) {
            $cursor->restoreState($state);

            return false;
        }

        $inlineContext->getContainer()->appendChild(new Popover($description));

        return true;
    }
}
