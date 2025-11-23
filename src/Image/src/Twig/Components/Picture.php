<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
#[AsTwigComponent('picture', template: '@Image/components/picture.html.twig')]
final class Picture extends Img
{
    protected function getImage(array $modifiers = [], bool $applyFallback = false): string
    {
        // For Picture component, don't automatically add $this->ratio
        // as it might be breakpoint-specific and passed via modifiers
        // Store original ratio and temporarily clear it
        $originalRatio = $this->ratio;
        $this->ratio = null;

        $result = parent::getImage($modifiers, $applyFallback);

        // Restore original ratio
        $this->ratio = $originalRatio;

        return $result;
    }

    public function getBreakpoints(): array
    {
        if (!$this->width) {
            return [];
        }

        // Parse ratios if they exist and cascade them
        $parsedRatios = $this->ratio ? $this->transformer->parseRatio($this->ratio) : [];
        $cascadedRatios = $this->cascadeRatios($parsedRatios);
        $hasArtDirection = !empty($cascadedRatios) && count(array_unique($cascadedRatios)) > 1;

        // Handle viewport width units
        if (str_contains($this->width, 'vw')) {
            $breakpoints = [];
            $parsedWidths = $this->transformer->parseWidth($this->width);
            $sizesAttribute = $this->transformer->getSizes($parsedWidths);
            $configuredBreakpoints = $this->transformer->getBreakpoints();
            $breakpointKeys = array_keys($configuredBreakpoints);
            $index = 0;

            foreach ($configuredBreakpoints as $breakpoint => $size) {
                $modifiers = ['width' => $size];

                // Apply cascaded ratio for this breakpoint
                if (isset($cascadedRatios[$breakpoint])) {
                    $modifiers['ratio'] = $cascadedRatios[$breakpoint];
                }

                // For art direction, use exclusive media query ranges
                // This ensures the browser picks the right aspect ratio
                if ($hasArtDirection) {
                    // For the last (largest) breakpoint, use only min-width
                    if ($index === count($breakpointKeys) - 1) {
                        $breakpoints[] = [
                            'media' => "(min-width: {$size}px)",
                            'srcset' => $this->getImage($modifiers, false) . " {$size}w",
                            'sizes' => $sizesAttribute,
                        ];
                    } else {
                        // Create exclusive range: min-width to just below next breakpoint
                        $nextSize = $configuredBreakpoints[$breakpointKeys[$index + 1]];
                        $breakpoints[] = [
                            'media' => "(min-width: {$size}px) and (max-width: " . ($nextSize - 1) . "px)",
                            'srcset' => $this->getImage($modifiers, false) . " {$size}w",
                            'sizes' => $sizesAttribute,
                        ];
                    }
                } else {
                    // Without art direction, use simple min-width
                    $breakpoints[] = [
                        'media' => "(min-width: {$size}px)",
                        'srcset' => $this->getImage($modifiers, false) . " {$size}w",
                        'sizes' => $sizesAttribute,
                    ];
                }

                ++$index;
            }

            return $breakpoints;
        }

        // Handle regular breakpoints
        if (!str_contains($this->width, ':')) {
            return [];
        }

        $breakpoints = [];
        $widths = $this->transformer->parseWidth($this->width);
        $configuredBreakpoints = $this->transformer->getBreakpoints();

        foreach ($widths as $breakpoint => $width) {
            if ('default' === $breakpoint) {
                continue;
            }

            if (isset($configuredBreakpoints[$breakpoint])) {
                $modifiers = ['width' => $width['value']];

                // Apply cascaded ratio for this breakpoint
                if (isset($cascadedRatios[$breakpoint])) {
                    $modifiers['ratio'] = $cascadedRatios[$breakpoint];
                }

                $breakpoints[] = [
                    'media' => "(max-width: {$configuredBreakpoints[$breakpoint]}px)",
                    'srcset' => $this->getImage($modifiers, false),
                ];
            }
        }

        return $breakpoints;
    }

    private function cascadeRatios(array $parsedRatios): array
    {
        if (empty($parsedRatios)) {
            return [];
        }

        $breakpointOrder = ['sm', 'md', 'lg', 'xl', '2xl'];
        $cascaded = [];
        $currentRatio = $parsedRatios['default'] ?? null;

        foreach ($breakpointOrder as $breakpoint) {
            // If this breakpoint has a specific ratio, use it and update current
            if (isset($parsedRatios[$breakpoint])) {
                $currentRatio = $parsedRatios[$breakpoint];
            }

            // Apply the current ratio to this breakpoint
            if ($currentRatio) {
                $cascaded[$breakpoint] = $currentRatio;
            }
        }

        return $cascaded;
    }

    public function getSizes(): ?string
    {
        if (str_contains($this->width, 'vw')) {
            return $this->width;
        }

        return null;
    }
}
