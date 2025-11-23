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
    private array $breakpoints;
    private array $breakpointOrder;

    public function __construct(array $breakpoints = [
        'sm' => 640,
        'md' => 768,
        'lg' => 1024,
        'xl' => 1280,
        '2xl' => 1536,
    ])
    {
        $this->breakpoints = $breakpoints;

        // Build dynamic breakpoint order based on provided breakpoints
        // Sort by value to maintain ascending order
        $sortedBreakpoints = $breakpoints;
        asort($sortedBreakpoints);
        $this->breakpointOrder = ['default', ...array_keys($sortedBreakpoints)];
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
                    || array_search($breakpoint, $this->breakpointOrder) < array_search($smallestBreakpoint, $this->breakpointOrder)
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
            foreach ($this->breakpointOrder as $breakpoint) {
                if ($firstFixedAfterVw && $breakpoint === $firstFixedAfterVw) {
                    // Found fixed width transition point, propagate fixed width to remaining breakpoints
                    $fixedValue = $widths[$firstFixedAfterVw];
                    foreach ($this->breakpointOrder as $nextBreakpoint) {
                        if (
                            array_search($nextBreakpoint, $this->breakpointOrder) >= array_search($breakpoint, $this->breakpointOrder)
                            && !isset($widths[$nextBreakpoint])
                        ) {
                            $widths[$nextBreakpoint] = $fixedValue;
                        }
                    }
                    break;
                }

                if (!isset($widths[$breakpoint])) {
                    $breakpointWidth = 'default' === $breakpoint ?
                        reset($this->breakpoints) :
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
            foreach ($this->breakpointOrder as $breakpoint) {
                if ($firstVwAfterFixed && $breakpoint === $firstVwAfterFixed) {
                    // Found viewport width transition point
                    $vwPercentage = (int) $widths[$breakpoint]['vw'];

                    // Calculate viewport widths for remaining breakpoints
                    foreach ($this->breakpointOrder as $vwBreakpoint) {
                        if (
                            array_search($vwBreakpoint, $this->breakpointOrder) >= array_search($breakpoint, $this->breakpointOrder)
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
                reset($this->breakpoints) :
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
        $sizes = [];

        // Sort breakpoints by value ascending for max-width logic
        $sortedBreakpoints = $this->breakpoints;
        asort($sortedBreakpoints);
        $sortedKeys = array_keys($sortedBreakpoints);

        // Iterate from smallest to largest
        foreach ($sortedKeys as $i => $key) {
            // Determine the value applicable for the range ENDING at this breakpoint.
            // For sm (640), the range is 0-640. This corresponds to 'default' width (mobile).
            // For md (768), the range is 640-768. This corresponds to 'sm' width.

            $sourceKey = 0 === $i ? 'default' : $sortedKeys[$i - 1];

            if (isset($widths[$sourceKey])) {
                $currentValue = $widths[$sourceKey];

                $sizes[] = [
                    'media' => sprintf('(max-width: %dpx)', $this->breakpoints[$key]),
                    'value' => $this->formatSizeValue($currentValue),
                ];
            }
        }

        $optimizedSizes = [];
        $count = count($sizes);
        for ($i = 0; $i < $count; $i++) {
            $current = $sizes[$i];
            $keep = true;

            // Look ahead for same value
            for ($j = $i + 1; $j < $count; $j++) {
                $next = $sizes[$j];
                // Check if the formatted value string is the same
                if ($current['value'] === $next['value']) {
                    // Found a larger breakpoint with same value. Current is redundant.
                    $keep = false;
                    break;
                } else {
                    // Value changed. Current is boundary. Keep it.
                    break;
                }
            }

            if ($keep) {
                $optimizedSizes[] = $current['media'] . ' ' . $current['value'];
            }
        }

        // Find fallback (default)
        // This corresponds to the range starting at the largest breakpoint.
        // i.e. widths[largest_breakpoint]
        $fallback = null;
        $largestKey = end($sortedKeys);
        if (isset($widths[$largestKey])) {
            $fallback = $this->formatSizeValue($widths[$largestKey]);
        }

        // If fallback matches the last size query, remove that query?
        // (max-width: 1536) 200px, 200px.
        if (!empty($optimizedSizes) && $fallback) {
            $lastSize = end($optimizedSizes);
            // $lastSize string format: "(max-width: ...) val"
            // Check if val matches fallback.
            if (str_ends_with($lastSize, ' ' . $fallback)) {
                array_pop($optimizedSizes);
            }
        }

        if ($fallback) {
            $optimizedSizes[] = $fallback;
        }

        return implode(', ', $optimizedSizes);
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
