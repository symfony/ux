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
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
final class Transformer
{
    private const BREAKPOINT_ORDER = ['default', 'sm', 'md', 'lg', 'xl', '2xl'];
    private array $breakpoints;

    public function __construct(array $breakpoints = [
        'sm' => 640,
        'md' => 768,
        'lg' => 1024,
        'xl' => 1280,
        '2xl' => 1536,
    ])
    {
        $this->breakpoints = $breakpoints;
    }

    public function parseWidth(string $width): array
    {
        $parts = preg_split('/\s+/', trim($width));
        $widths = [];
        $smallestBreakpoint = null;
        $firstVwAfterFixed = null;
        $firstFixedAfterVw = null;

        // First pass: collect explicit values and find transitions
        foreach ($parts as $part) {
            if (str_contains($part, ':')) {
                [$breakpoint, $value] = explode(':', $part);
                $normalized = $this->normalizeWidthValue($value, $breakpoint);
                $widths[$breakpoint] = $normalized;

                // Track transitions
                if ('0' !== $normalized['vw'] && isset($widths['default']) && '0' === $widths['default']['vw']) {
                    $firstVwAfterFixed = $breakpoint;
                }
                if ('0' === $normalized['vw'] && isset($widths['default']) && '0' !== $widths['default']['vw']) {
                    $firstFixedAfterVw = $breakpoint;
                }

                // Track the smallest breakpoint
                if (
                    !$smallestBreakpoint
                    || array_search($breakpoint, self::BREAKPOINT_ORDER) < array_search($smallestBreakpoint, self::BREAKPOINT_ORDER)
                ) {
                    $smallestBreakpoint = $breakpoint;
                }
            } else {
                $widths['default'] = $this->normalizeWidthValue($part, 'default');
            }
        }

        // If no default width is set but we have breakpoints, use the smallest breakpoint as default
        if (!isset($widths['default']) && $smallestBreakpoint) {
            $widths['default'] = $widths[$smallestBreakpoint];
        }

        // Handle viewport width calculations and transitions
        if (isset($widths['default']) && '0' !== $widths['default']['vw']) {
            $vwPercentage = (int) $widths['default']['vw'];

            // Pre-calculate all viewport widths up to fixed width transition
            foreach (self::BREAKPOINT_ORDER as $breakpoint) {
                if ($firstFixedAfterVw && $breakpoint === $firstFixedAfterVw) {
                    // Found fixed width transition point, propagate fixed width to remaining breakpoints
                    $fixedValue = $widths[$firstFixedAfterVw];
                    foreach (self::BREAKPOINT_ORDER as $nextBreakpoint) {
                        if (
                            array_search($nextBreakpoint, self::BREAKPOINT_ORDER) >= array_search($breakpoint, self::BREAKPOINT_ORDER)
                            && !isset($widths[$nextBreakpoint])
                        ) {
                            $widths[$nextBreakpoint] = $fixedValue;
                        }
                    }
                    break;
                }

                if (!isset($widths[$breakpoint])) {
                    $breakpointWidth = 'default' === $breakpoint ?
                        $this->breakpoints['sm'] :
                        $this->breakpoints[$breakpoint];

                    $pixelWidth = (int) ($breakpointWidth * ($vwPercentage / 100));

                    $widths[$breakpoint] = [
                        'value' => $pixelWidth,
                        'vw' => (string) $vwPercentage,
                    ];
                }
            }
        }
        // Handle fixed width cases
        elseif (isset($widths['default']) && '0' === $widths['default']['vw']) {
            $lastValue = $widths['default'];

            // Propagate fixed width to all breakpoints
            foreach (self::BREAKPOINT_ORDER as $breakpoint) {
                if ($firstVwAfterFixed && $breakpoint === $firstVwAfterFixed) {
                    // Found viewport width transition point
                    $vwPercentage = (int) $widths[$breakpoint]['vw'];

                    // Calculate viewport widths for remaining breakpoints
                    foreach (self::BREAKPOINT_ORDER as $vwBreakpoint) {
                        if (
                            array_search($vwBreakpoint, self::BREAKPOINT_ORDER) >= array_search($breakpoint, self::BREAKPOINT_ORDER)
                            && !isset($widths[$vwBreakpoint])
                        ) {
                            $breakpointWidth = $this->breakpoints[$vwBreakpoint];
                            $pixelWidth = (int) ($breakpointWidth * ($vwPercentage / 100));

                            $widths[$vwBreakpoint] = [
                                'value' => $pixelWidth,
                                'vw' => (string) $vwPercentage,
                            ];
                        }
                    }
                    break;
                }

                if (!isset($widths[$breakpoint])) {
                    $widths[$breakpoint] = $lastValue;
                } else {
                    $lastValue = $widths[$breakpoint];
                }
            }
        }

        return $widths;
    }

    private function normalizeWidthValue(string $value, string $breakpoint = 'default'): array
    {
        $isVw = str_ends_with($value, 'vw');
        $numericValue = (int) preg_replace('/[^0-9]/', '', $value);

        if ($isVw) {
            $breakpointWidth = 'default' === $breakpoint ?
                $this->breakpoints['sm'] :
                $this->breakpoints[$breakpoint];

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

    public function getSizes(array $widths): string
    {
        // Special case: if it's just a viewport width with no breakpoints
        // and all breakpoints have the same vw value
        if (isset($widths['default']) && '100' === $widths['default']['vw']) {
            $allSame = true;
            foreach ($widths as $key => $width) {
                if ('default' !== $key && isset($width['vw']) && '100' !== $width['vw']) {
                    $allSame = false;
                    break;
                }
            }
            if ($allSame) {
                return '100vw';
            }
        }

        $sizes = [];
        $breakpointKeys = array_keys($this->breakpoints);

        // Find the largest explicit value for default size (no media query)
        $largestValue = null;
        foreach (array_reverse($breakpointKeys) as $key) {
            if (isset($widths[$key])) {
                $largestValue = $widths[$key];
                break;
            }
        }

        // Process breakpoints from largest to smallest
        $sizeVariants = [];

        foreach (array_reverse($breakpointKeys) as $i => $key) {
            if (isset($widths[$key])) {
                // Find the next breakpoint that has a value
                $nextValue = null;
                for ($j = $i + 1; $j < \count($breakpointKeys); ++$j) {
                    $nextKey = array_reverse($breakpointKeys)[$j];
                    if (isset($widths[$nextKey])) {
                        $nextValue = $widths[$nextKey];
                        break;
                    }
                }

                // If no next breakpoint value found and we have a default value
                if (!$nextValue && isset($widths['default'])) {
                    $nextValue = $widths['default'];
                }

                // Add current value to size variants
                $sizeVariants[] = [
                    'size' => $this->formatSizeValue($widths[$key]),
                    'screenMaxWidth' => $this->breakpoints[$key],
                    'media' => \sprintf('(max-width: %dpx)', $this->breakpoints[$key]),
                ];

                // If next value is different, add it at this breakpoint
                if ($nextValue && !$this->isSameValue($widths[$key], $nextValue)) {
                    $sizeVariants[] = [
                        'size' => $this->formatSizeValue($nextValue),
                        'screenMaxWidth' => $this->breakpoints[$key],
                        'media' => \sprintf('(max-width: %dpx)', $this->breakpoints[$key]),
                    ];
                }
            }
        }

        // Sort variants by screen width (largest to smallest)
        usort($sizeVariants, fn($a, $b) => $b['screenMaxWidth'] - $a['screenMaxWidth']);

        // Add size variants to sizes array (media queries come first)
        foreach ($sizeVariants as $variant) {
            $sizes[] = $variant['media'] . ' ' . $variant['size'];
        }

        // Add default value if it exists and differs from sm breakpoint
        if (
            isset($widths['default'])
            && (!isset($widths['sm']) || !$this->isSameValue($widths['default'], $widths['sm']))
        ) {
            $sizes[] = \sprintf(
                '(max-width: %dpx) %s',
                $this->breakpoints['sm'],
                $this->formatSizeValue($widths['default'])
            );
        }

        // Add the largest value as the default size at the END (no media query)
        // This is the fallback size that browsers use when no media queries match
        if ($largestValue) {
            $sizes[] = $this->formatSizeValue($largestValue);
        }

        return implode(', ', array_unique($sizes));
    }

    public function getSrcset(string $src, array $widths, callable $imageCallback): string
    {
        $srcset = [];
        $seenWidths = [];

        foreach ($widths as $width) {
            // Only include positive widths and deduplicate by width value
            if ($width['value'] > 0 && !isset($seenWidths[$width['value']])) {
                $srcset[] = \sprintf(
                    '%s %sw',
                    $imageCallback(['width' => $width['value']]),
                    $width['value']
                );
                $seenWidths[$width['value']] = true;
            }
        }

        return implode(', ', $srcset);
    }

    private function isSameValue(array $value1, array $value2): bool
    {
        return $value1['value'] === $value2['value'] && $value1['vw'] === $value2['vw'];
    }

    private function formatSizeValue(array $width): string
    {
        return '0' !== $width['vw']
            ? $width['vw'] . 'vw'
            : $width['value'] . 'px';
    }

    public function getInitialWidth(array $widths, string $pattern): int
    {
        if (preg_match('/^\d+vw/', $pattern)) {
            // If pattern starts with viewport width
            $smallestWidth = \PHP_INT_MAX;
            foreach ($widths as $width) {
                if ($width['value'] < $smallestWidth && '0' !== $width['vw']) {
                    $smallestWidth = $width['value'];
                }
            }

            return $smallestWidth;
        }

        // For fixed widths or patterns starting with fixed width
        return $widths['default']['value'];
    }

    // Add new method to handle density-based width calculations
    public function getDensityBasedWidths(int $baseWidth, string $densities): array
    {
        $densityMultipliers = array_map(
            fn($d) => (float) str_replace('x', '', trim($d)),
            explode(' ', $densities)
        );

        $widths = [];
        foreach ($densityMultipliers as $multiplier) {
            $widths[] = (int) ($baseWidth * $multiplier);
        }

        sort($widths);

        return $widths;
    }

    public function getBreakpoints(): array
    {
        return $this->breakpoints;
    }

    public function parseRatio(?string $ratio): array
    {
        if (!$ratio) {
            return [];
        }

        $parts = preg_split('/\s+/', trim($ratio));
        $ratios = [];

        foreach ($parts as $part) {
            // Check if it's a breakpoint-specific ratio (e.g., "sm:1:1" or "md:16:9")
            // Match pattern: breakpoint:(number):(number)
            if (preg_match('/^(' . implode('|', array_keys($this->breakpoints)) . '):(\d+:\d+)$/', $part, $matches)) {
                $breakpoint = $matches[1];
                $ratioValue = $matches[2];
                $ratios[$breakpoint] = $ratioValue;
            } elseif (preg_match('/^\d+:\d+$/', $part)) {
                // It's a default ratio value (e.g., "16:9")
                $ratios['default'] = $part;
            }
        }

        return $ratios;
    }
}
