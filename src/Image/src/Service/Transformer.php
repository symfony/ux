<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Service;

/**
 * Transforms responsive image configurations into HTML attributes.
 *
 * Handles width parsing, breakpoint calculations, srcset and sizes generation.
 *
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
class Transformer
{
    private const BREAKPOINT_ORDER = ['default', 'sm', 'md', 'lg', 'xl', '2xl'];

    /**
     * @param array<string, int> $breakpoints
     */
    public function __construct(
        private readonly array $breakpoints = [
            'sm' => 640,
            'md' => 768,
            'lg' => 1024,
            'xl' => 1280,
            '2xl' => 1536,
        ],
    ) {
    }

    /**
     * Parses a width string like "100vw sm:50vw md:400px" into structured data.
     *
     * Returns an array keyed by breakpoint name, each containing:
     *   - 'value': pixel width
     *   - 'vw': viewport width percentage (0 if fixed)
     */
    public function parseWidth(string $width): array
    {
        $parts = preg_split('/\s+/', trim($width));
        $widths = [];
        $smallestBreakpoint = null;

        foreach ($parts as $part) {
            if (str_contains($part, ':')) {
                [$breakpoint, $value] = explode(':', $part, 2);
                $normalized = $this->normalizeWidthValue($value, $breakpoint);
                $widths[$breakpoint] = $normalized;

                if (!$smallestBreakpoint
                    || array_search($breakpoint, self::BREAKPOINT_ORDER, true) < array_search($smallestBreakpoint, self::BREAKPOINT_ORDER, true)) {
                    $smallestBreakpoint = $breakpoint;
                }
            } else {
                $widths['default'] = $this->normalizeWidthValue($part, 'default');
            }
        }

        // If no default but we have breakpoints, use smallest as default
        if (!isset($widths['default']) && $smallestBreakpoint) {
            $widths['default'] = $widths[$smallestBreakpoint];
        }

        // Calculate viewport widths
        if (isset($widths['default']) && '0' !== $widths['default']['vw']) {
            $this->calculateViewportWidths($widths);
        } else {
            $this->propagateFixedWidths($widths);
        }

        return $widths;
    }

    /**
     * Generates the sizes attribute from parsed widths.
     */
    public function getSizes(array $widths): string
    {
        if (isset($widths['default']) && '100' === $widths['default']['vw']) {
            return '100vw';
        }

        $sizes = [];
        $breakpointKeys = array_keys($this->breakpoints);

        // Find largest explicit value for default
        $largestValue = null;
        foreach (array_reverse($breakpointKeys) as $key) {
            if (isset($widths[$key])) {
                $largestValue = $widths[$key];
                break;
            }
        }

        if ($largestValue) {
            $sizes[] = $this->formatSizeValue($largestValue);
        }

        // Process breakpoints largest to smallest
        foreach (array_reverse($breakpointKeys) as $i => $key) {
            if (isset($widths[$key])) {
                $nextValue = null;
                for ($j = $i + 1; $j < \count($breakpointKeys); ++$j) {
                    $nextKey = array_reverse($breakpointKeys)[$j];
                    if (isset($widths[$nextKey])) {
                        $nextValue = $widths[$nextKey];
                        break;
                    }
                }

                if (!$nextValue && isset($widths['default'])) {
                    $nextValue = $widths['default'];
                }

                $sizes[] = \sprintf('(max-width: %dpx) %s',
                    $this->breakpoints[$key],
                    $this->formatSizeValue($widths[$key])
                );

                if ($nextValue && !$this->isSameValue($widths[$key], $nextValue)) {
                    $sizes[] = \sprintf('(max-width: %dpx) %s',
                        $this->breakpoints[$key],
                        $this->formatSizeValue($nextValue)
                    );
                }
            }
        }

        return implode(', ', array_unique($sizes));
    }

    /**
     * Generates the srcset attribute from parsed widths.
     *
     * @param callable $imageCallback Function that takes modifiers and returns image URL
     */
    public function getSrcset(string $src, array $widths, callable $imageCallback): string
    {
        $srcset = [];
        foreach ($widths as $width) {
            if ($width['value'] > 0) {
                $srcset[] = \sprintf('%s %sw',
                    $imageCallback(['width' => $width['value']]),
                    $width['value']
                );
            }
        }

        return implode(', ', $srcset);
    }

    /**
     * Returns the initial width for the fallback/src image.
     */
    public function getInitialWidth(array $widths, string $pattern): int
    {
        if (preg_match('/^\d+vw/', $pattern)) {
            $smallestWidth = \PHP_INT_MAX;
            foreach ($widths as $width) {
                if ($width['value'] < $smallestWidth && '0' !== $width['vw']) {
                    $smallestWidth = $width['value'];
                }
            }

            return $smallestWidth;
        }

        return $widths['default']['value'];
    }

    /**
     * Calculates widths for density-based srcset (e.g., x1 x2).
     */
    public function getDensityBasedWidths(int $baseWidth, string $densities): array
    {
        $multipliers = array_map(
            fn ($d) => (float) str_replace('x', '', trim($d)),
            explode(' ', $densities)
        );

        $widths = [];
        foreach ($multipliers as $multiplier) {
            $widths[] = (int) ($baseWidth * $multiplier);
        }

        sort($widths);

        return $widths;
    }

    /**
     * Parses a ratio string like "sm:1:1 md:16:9" into breakpoint-ratio pairs.
     */
    public function parseRatio(string $ratio): array
    {
        $parts = preg_split('/\s+/', trim($ratio));
        $ratios = [];

        foreach ($parts as $part) {
            if (str_contains($part, ':')) {
                // Handle "sm:1:1" — split on first colon only
                [$breakpoint, $value] = explode(':', $part, 2);
                $ratios[$breakpoint] = $value;
            } else {
                $ratios['default'] = $part;
            }
        }

        return $ratios;
    }

    /**
     * Cascades ratios across breakpoints (CSS-like inheritance).
     */
    public function cascadeRatios(array $parsedRatios): array
    {
        if (empty($parsedRatios)) {
            return [];
        }

        $breakpointOrder = ['sm', 'md', 'lg', 'xl', '2xl'];
        $cascaded = [];
        $currentRatio = $parsedRatios['default'] ?? null;

        foreach ($breakpointOrder as $breakpoint) {
            if (isset($parsedRatios[$breakpoint])) {
                $currentRatio = $parsedRatios[$breakpoint];
            }

            if ($currentRatio) {
                $cascaded[$breakpoint] = $currentRatio;
            }
        }

        return $cascaded;
    }

    public function getBreakpoints(): array
    {
        return $this->breakpoints;
    }

    private function normalizeWidthValue(string $value, string $breakpoint = 'default'): array
    {
        $isVw = str_ends_with($value, 'vw');
        $numericValue = (int) preg_replace('/[^0-9]/', '', $value);

        if ($isVw) {
            $breakpointWidth = 'default' === $breakpoint
                ? $this->breakpoints['sm']
                : $this->breakpoints[$breakpoint];

            $pixelWidth = (int) ($breakpointWidth * ($numericValue / 100));

            return [
                'value' => $pixelWidth,
                'vw' => (string) $numericValue,
            ];
        }

        return [
            'value' => $numericValue,
            'vw' => '0',
        ];
    }

    private function calculateViewportWidths(array &$widths): void
    {
        $vwPercentage = (int) $widths['default']['vw'];

        foreach (self::BREAKPOINT_ORDER as $breakpoint) {
            if (!isset($widths[$breakpoint])) {
                $breakpointWidth = 'default' === $breakpoint
                    ? $this->breakpoints['sm']
                    : $this->breakpoints[$breakpoint];

                $pixelWidth = (int) ($breakpointWidth * ($vwPercentage / 100));

                $widths[$breakpoint] = [
                    'value' => $pixelWidth,
                    'vw' => (string) $vwPercentage,
                ];
            }
        }
    }

    private function propagateFixedWidths(array &$widths): void
    {
        $lastValue = $widths['default'];

        foreach (self::BREAKPOINT_ORDER as $breakpoint) {
            if (!isset($widths[$breakpoint])) {
                $widths[$breakpoint] = $lastValue;
            } else {
                $lastValue = $widths[$breakpoint];
            }
        }
    }

    private function isSameValue(array $a, array $b): bool
    {
        return $a['value'] === $b['value'] && $a['vw'] === $b['vw'];
    }

    private function formatSizeValue(array $width): string
    {
        return '0' !== $width['vw']
            ? $width['vw'].'vw'
            : $width['value'].'px';
    }
}
