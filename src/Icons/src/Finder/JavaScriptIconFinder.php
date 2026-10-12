<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Finder;

use Symfony\Component\Finder\Finder;
use Symfony\UX\Icons\IconFinderInterface;
use Symfony\UX\Icons\JavaScript\IconsDumper;

/**
 * Finds the icons imported in JavaScript files from the module dumped by {@see IconsDumper}.
 *
 *     import { LucideTrash2Icon, HeroiconsXMarkIcon as Close } from '../var/icons/index.js';
 *
 * Export names are not reversible ("lucide:trash-2" and "lucide:trash2" are both exported as "LucideTrash2Icon"), so every candidate name is returned: the lock command skips unknown icon sets and icons.
 *
 * @internal
 */
final class JavaScriptIconFinder implements IconFinderInterface
{
    // import { A, B as C } from '../var/icons/index.js', or import type { A } from '@/icons'
    private const IMPORT_PATTERN = '#\bimport\s+(?:type\s+)?\{([^}]*)\}\s*from\s*([\'"])([^\'"]*icons[^\'"]*)\2#i';

    /**
     * @param list<string> $directories
     */
    public function __construct(
        private readonly array $directories,
    ) {
    }

    public function icons(): iterable
    {
        $directories = array_filter($this->directories, is_dir(...));
        if (!$directories) {
            return [];
        }

        $files = new Finder()
            ->files()
            ->in($directories)
            ->exclude(['node_modules', 'vendor'])
            ->name(['*.js', '*.mjs', '*.jsx', '*.ts', '*.mts', '*.tsx', '*.vue', '*.svelte']);

        $found = [];
        foreach ($files as $file) {
            if (!preg_match_all(self::IMPORT_PATTERN, $file->getContents(), $imports)) {
                continue;
            }

            foreach ($imports[1] as $specifiers) {
                foreach (explode(',', $specifiers) as $specifier) {
                    // "LucideTrash2Icon as TrashIcon" => "LucideTrash2Icon", "type UXIconData" => skipped
                    $exportName = preg_split('/\s+as\s+/', trim($specifier))[0];
                    $found[] = self::iconNames($exportName);
                }
            }
        }

        return array_values(array_unique(array_merge(...$found)));
    }

    /**
     * Returns the "prefix:name" icon names that {@see IconsDumper::exportName()} transforms into the given export name.
     *
     * @return list<string>
     */
    public static function iconNames(string $exportName): array
    {
        if (!preg_match('/^_?([A-Z0-9][A-Za-z0-9]*)Icon$/', $exportName, $matches)) {
            return [];
        }

        // "LucideTrash2Icon" => ["Lucide", "Trash", "2"]
        preg_match_all('/[A-Z][a-z]*|[a-z]+|[0-9]+/', $matches[1], $words);
        $words = $words[0];

        $names = [];
        for ($i = 1, $count = \count($words); $i < $count; ++$i) {
            if (ctype_lower($words[$i])) {
                // a lowercase word is glued to the preceding number ("Mdi2xl"), it cannot start the icon name
                continue;
            }

            foreach (self::slugs(\array_slice($words, 0, $i)) as $prefix) {
                foreach (self::slugs(\array_slice($words, $i)) as $name) {
                    $names[] = $prefix.':'.$name;
                }
            }
        }

        return $names;
    }

    /**
     * Joins words with hyphens, the ambiguous ones yielding both variants.
     *
     *  - a number may follow a hyphen or not ("Trash2" => "trash-2", "trash2"),
     *  - a lowercase word follows a number without hyphen ("2xl" is exported as "2xl", "2-xl" as "2Xl").
     *
     * @param list<string> $words
     *
     * @return list<string>
     */
    private static function slugs(array $words): array
    {
        $slugs = [strtolower(array_shift($words))];
        foreach ($words as $word) {
            $separators = match (true) {
                ctype_digit($word) => ['-', ''],
                ctype_lower($word) => [''],
                default => ['-'],
            };

            $next = [];
            foreach ($slugs as $slug) {
                foreach ($separators as $separator) {
                    $next[] = $slug.$separator.strtolower($word);
                }
            }
            $slugs = $next;
        }

        return $slugs;
    }
}
