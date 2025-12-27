<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\CommonMark\Extension\ToolkitPreview\Parser;

use App\Enum\ToolkitKitId;
use App\Service\CommonMark\Extension\ToolkitPreview\Node\ToolkitPreview;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Parser\Block\AbstractBlockContinueParser;
use League\CommonMark\Parser\Block\BlockContinue;
use League\CommonMark\Parser\Block\BlockContinueParserInterface;
use League\CommonMark\Parser\Block\BlockStart;
use League\CommonMark\Parser\Block\BlockStartParserInterface;
use League\CommonMark\Parser\Cursor;
use League\CommonMark\Parser\MarkdownParserStateInterface;

final class ToolkitPreviewParser extends AbstractBlockContinueParser
{
    /**
     * @var array<string>
     */
    private array $strings = [];

    public function __construct(
        private readonly ToolkitPreview $toolkitPreview,
    ) {
    }

    public static function createBlockStartParser(): BlockStartParserInterface
    {
        return new class implements BlockStartParserInterface {
            public function tryStart(Cursor $cursor, MarkdownParserStateInterface $parserState): ?BlockStart
            {
                if ($cursor->isIndented()) {
                    return BlockStart::none();
                }

                if (null === $cursor->match('/^\[toolkit-preview /')) {
                    return BlockStart::none();
                }

                $remainder = rtrim($cursor->getRemainder(), ']');
                $options = json_validate($remainder) ? json_decode($remainder, true) : [];

                $kitId = $options['kit'] ?? throw new \LogicException('The "kit" option is required for toolkit-preview code blocks.');
                $kitId = ToolkitKitId::tryFrom($kitId) ?? throw new \LogicException(\sprintf('Invalid toolkit kit ID "%s" provided for toolkit-preview code block.', $kitId));
                unset($options['kit']);

                return BlockStart::of(new ToolkitPreviewParser(
                    new ToolkitPreview($kitId, $options),
                ))->at($cursor);
            }
        };
    }

    public function getBlock(): AbstractBlock
    {
        return $this->toolkitPreview;
    }

    public function canContain(AbstractBlock $childBlock): bool
    {
        return false;
    }

    public function tryContinue(Cursor $cursor, BlockContinueParserInterface $activeBlockParser): ?BlockContinue
    {
        if ($cursor->isBlank()) {
            return BlockContinue::at($cursor);
        }

        $remaining = $cursor->getRemainder();
        if (str_contains($remaining, '[/toolkit-preview]')) {
            return BlockContinue::finished();
        }

        return BlockContinue::at($cursor);
    }

    public function addLine(string $line): void
    {
        $this->strings[] = $line;
    }

    public function closeBlock(): void
    {
        if ([] === $this->strings) {
            return;
        }

        $this->toolkitPreview->setLiteral(implode("\n", \array_slice($this->strings, 1)));
    }
}
