<?php

namespace App\Util;

use function Symfony\Component\String\u;

class SourceCleaner
{
    public static function cleanupPhpFile(string $contents, bool $removeClass = false): string
    {
        $contents = u($contents)
            ->replace("<?php\n", '')
            ->replaceMatches('/namespace[^\n]*/', '');

        if ($removeClass) {
            $contents = $contents->replaceMatches('/class[^\n]*\n{/', '')
                ->trim('{}')
                // remove use statements
                ->replaceMatches('/^use [^\n]*$/m', '');

            // unindent all lines by 4 spaces
            $lines = explode("\n", $contents);
            $lines = array_map(function (string $line) {
                return substr($line, 4);
            }, $lines);
            $contents = u(implode("\n", $lines));
        }

        return $contents->trim()->toString();
    }

    public static function processTerminalLines(string $content): string
    {
        $lines = explode("\n", $content);

        $lines = array_map(function (string $line) {
            $line = trim($line);

            if (!$line) {
                return '';
            }

            // comment lines
            if (str_starts_with($line, '//')) {
                return sprintf('<span class="hljs-comment">%s</span>', $line);
            }

            return '<span class="hljs-prompt">$ </span>'.$line;
        }, $lines);

        return trim(implode("\n", $lines));
    }
}
