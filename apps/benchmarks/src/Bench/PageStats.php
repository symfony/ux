<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Bench;

final class PageStats
{
    private const NOUNS = [
        'components' => ['component', 'components'],
        'rows' => ['row', 'rows'],
        'icons' => ['icon', 'icons'],
        'markers' => ['marker', 'markers'],
        'live_children' => ['live child', 'live children'],
    ];

    /**
     * @param array<string, int> $counts
     */
    private function __construct(
        private readonly array $counts,
        private readonly int $classAttributes,
        private readonly int $classes,
        private readonly int $uniqueClasses,
        private readonly int $bytes,
    ) {
    }

    public static function fromHtml(string $html): self
    {
        $document = \Dom\HTMLDocument::createFromString($html, \LIBXML_NOERROR);

        $counts = [
            'components' => $document->querySelectorAll('[data-slot]')->length,
            'rows' => $document->querySelectorAll('tbody tr')->length,
            'icons' => $document->querySelectorAll('svg')->length,
            'markers' => self::countMarkers($document),
            'live_children' => $document->querySelectorAll('[data-live-fingerprint-value]')->length,
        ];

        preg_match_all('/class="([^"]*)"/', $html, $matches);
        $names = [];
        foreach ($matches[1] as $value) {
            array_push($names, ...preg_split('/\s+/', $value, -1, \PREG_SPLIT_NO_EMPTY));
        }

        return new self($counts, \count($matches[1]), \count($names), \count(array_unique($names)), \strlen($html));
    }

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        return $this->counts;
    }

    public function holds(): string
    {
        $holds = [];
        foreach ($this->counts as $key => $count) {
            if (0 !== $count) {
                [$singular, $plural] = self::NOUNS[$key];
                $holds[] = \sprintf('%s %s', number_format($count), 1 === $count ? $singular : $plural);
            }
        }

        return [] === $holds ? '-' : implode(', ', $holds);
    }

    public function classAttributes(): int
    {
        return $this->classAttributes;
    }

    public function classes(): int
    {
        return $this->classes;
    }

    public function uniqueClasses(): int
    {
        return $this->uniqueClasses;
    }

    public function bytes(): int
    {
        return $this->bytes;
    }

    private static function countMarkers(\Dom\HTMLDocument $document): int
    {
        foreach ($document->querySelectorAll('*') as $element) {
            foreach ($element->attributes as $attribute) {
                if (str_ends_with($attribute->name, '-map-markers-value')) {
                    $markers = json_decode($attribute->value, true);

                    return \is_array($markers) ? \count($markers) : 0;
                }
            }
        }

        return 0;
    }
}
