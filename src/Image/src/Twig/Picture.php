<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Twig;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Art direction Twig component for different crops per breakpoint.
 *
 * Usage:
 *   <twig:ux:picture src="/images/hero.jpg" alt="Hero" width="100vw md:80vw" ratio="sm:1:1 md:16:9" />
 *
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
#[AsTwigComponent('ux:picture', template: '@Image/components/picture.html.twig')]
final class Picture extends Img
{
    protected function getImage(array $modifiers = [], bool $applyFallback = false): string
    {
        // Don't auto-apply ratio — it may be breakpoint-specific
        $originalRatio = $this->ratio;
        $this->ratio = null;

        $result = parent::getImage($modifiers, $applyFallback);

        $this->ratio = $originalRatio;

        return $result;
    }

    public function getBreakpoints(): array
    {
        if (!$this->width) {
            return [];
        }

        $parsedRatios = $this->ratio ? $this->transformer->parseRatio($this->ratio) : [];
        $cascadedRatios = $this->transformer->cascadeRatios($parsedRatios);
        $hasArtDirection = !empty($cascadedRatios) && \count(array_unique($cascadedRatios)) > 1;

        if (str_contains($this->width, 'vw')) {
            $breakpoints = [];
            $parsedWidths = $this->transformer->parseWidth($this->width);
            $sizesAttribute = $this->transformer->getSizes($parsedWidths);
            $configuredBreakpoints = $this->transformer->getBreakpoints();
            $breakpointKeys = array_keys($configuredBreakpoints);
            $index = 0;

            foreach ($configuredBreakpoints as $breakpoint => $size) {
                $modifiers = ['width' => $size];

                if (isset($cascadedRatios[$breakpoint])) {
                    $modifiers['ratio'] = $cascadedRatios[$breakpoint];
                }

                if ($hasArtDirection) {
                    if ($index === \count($breakpointKeys) - 1) {
                        $breakpoints[] = [
                            'media' => "(min-width: {$size}px)",
                            'srcset' => $this->getImage($modifiers, false)." {$size}w",
                            'sizes' => $sizesAttribute,
                        ];
                    } else {
                        $nextSize = $configuredBreakpoints[$breakpointKeys[$index + 1]];
                        $breakpoints[] = [
                            'media' => "(min-width: {$size}px) and (max-width: ".($nextSize - 1)."px)",
                            'srcset' => $this->getImage($modifiers, false)." {$size}w",
                            'sizes' => $sizesAttribute,
                        ];
                    }
                } else {
                    $breakpoints[] = [
                        'media' => "(min-width: {$size}px)",
                        'srcset' => $this->getImage($modifiers, false)." {$size}w",
                        'sizes' => $sizesAttribute,
                    ];
                }

                ++$index;
            }

            return $breakpoints;
        }

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

    public function getSizes(): ?string
    {
        if (str_contains($this->width, 'vw')) {
            return $this->width;
        }

        return null;
    }
}
