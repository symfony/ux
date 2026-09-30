<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Installer;

use Symfony\Component\Console\Formatter\OutputFormatter;

/**
 * Turns the output of "git diff" into console lines, highlighting the changed part of each modified line.
 *
 * The header is dropped, since it only repeats the file paths.
 * A block of removed lines followed by as many added lines is compared line by line: what is left once the common start and end are removed is the changed part.
 *
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class DiffHighlighter
{
    /**
     * @return list<string>
     */
    public function highlight(string $diff): array
    {
        $lines = [];
        $removedLines = [];
        $addedLines = [];
        $inHunks = false;

        foreach (explode("\n", rtrim($diff, "\n")) as $line) {
            $inHunks = $inHunks || str_starts_with($line, '@@ ');
            if (!$inHunks) {
                continue;
            }

            if (str_starts_with($line, '-')) {
                $removedLines[] = substr($line, 1);
                continue;
            }

            if (str_starts_with($line, '+')) {
                $addedLines[] = substr($line, 1);
                continue;
            }

            array_push($lines, ...$this->highlightBlock($removedLines, $addedLines));
            $removedLines = [];
            $addedLines = [];

            $escapedLine = OutputFormatter::escape($line);
            $lines[] = str_starts_with($line, '@@ ') ? \sprintf('<fg=cyan>%s</>', $escapedLine) : $escapedLine;
        }

        array_push($lines, ...$this->highlightBlock($removedLines, $addedLines));

        return $lines;
    }

    /**
     * @param list<string> $removedLines
     * @param list<string> $addedLines
     *
     * @return list<string>
     */
    private function highlightBlock(array $removedLines, array $addedLines): array
    {
        $comparable = \count($removedLines) === \count($addedLines);
        $lines = [];

        foreach ($removedLines as $i => $removedLine) {
            $lines[] = $this->highlightLine('-', 'red', $removedLine, $comparable ? $addedLines[$i] : null);
        }

        foreach ($addedLines as $i => $addedLine) {
            $lines[] = $this->highlightLine('+', 'green', $addedLine, $comparable ? $removedLines[$i] : null);
        }

        return $lines;
    }

    private function highlightLine(string $sign, string $color, string $line, ?string $otherLine): string
    {
        $chars = mb_str_split($line);
        $otherChars = mb_str_split($otherLine ?? '');

        $startLength = 0;
        $maxLength = min(\count($chars), \count($otherChars));
        while ($startLength < $maxLength && $chars[$startLength] === $otherChars[$startLength]) {
            ++$startLength;
        }

        $endLength = 0;
        $maxLength -= $startLength;
        $lastIndex = \count($chars) - 1;
        $otherLastIndex = \count($otherChars) - 1;
        while ($endLength < $maxLength && $chars[$lastIndex - $endLength] === $otherChars[$otherLastIndex - $endLength]) {
            ++$endLength;
        }

        $changedLength = \count($chars) - $startLength - $endLength;
        if (0 === $changedLength || $changedLength === \count($chars)) {
            return \sprintf('<fg=%s>%s</>', $color, OutputFormatter::escape($sign.$line));
        }

        $start = implode('', \array_slice($chars, 0, $startLength));
        $changed = implode('', \array_slice($chars, $startLength, $changedLength));
        $end = implode('', \array_slice($chars, $startLength + $changedLength));

        $highlightedLine = \sprintf('<fg=%s>%s</>', $color, OutputFormatter::escape($sign.$start));
        $highlightedLine .= \sprintf('<fg=%s;options=reverse>%s</>', $color, OutputFormatter::escape($changed));
        if ('' !== $end) {
            $highlightedLine .= \sprintf('<fg=%s>%s</>', $color, OutputFormatter::escape($end));
        }

        return $highlightedLine;
    }
}
