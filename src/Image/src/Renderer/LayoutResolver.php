<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Renderer;

use Symfony\UX\Image\Layout;

/**
 * Derives the srcset candidates, the sizes attribute and the layout styles.
 *
 * Ported from unpic (packages/core/src/base.ts).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class LayoutResolver
{
    /** @var list<int> */
    public const DEFAULT_RESOLUTIONS = [
        6016, 5120, 4480, 3840, 3200, 2560, 2048, 1920, 1668, 1280, 1080, 960, 828, 750, 640,
    ];

    /**
     * @return list<int> ascending, deduplicated
     */
    public function breakpoints(Layout $layout, ?int $width): array
    {
        if (Layout::FullWidth === $layout) {
            $candidates = self::DEFAULT_RESOLUTIONS;
        } elseif (null === $width) {
            return [];
        } elseif (Layout::Fixed === $layout) {
            $candidates = [$width, $width * 2];
        } else {
            $doubled = $width * 2;
            $candidates = [$width, $doubled, ...array_filter(self::DEFAULT_RESOLUTIONS, static fn (int $w): bool => $w < $doubled)];
        }

        $candidates = array_values(array_unique($candidates));
        sort($candidates);

        return $candidates;
    }

    public function sizes(Layout $layout, ?int $width): ?string
    {
        if (Layout::FullWidth === $layout) {
            return '100vw';
        }
        if (null === $width) {
            return null;
        }

        return match ($layout) {
            Layout::Fixed => \sprintf('%dpx', $width),
            Layout::Constrained => \sprintf('(min-width: %1$dpx) %1$dpx, 100vw', $width),
        };
    }

    /**
     * @return array<string, string>
     */
    public function style(Layout $layout, ?int $width, ?int $height, string $objectFit = 'cover'): array
    {
        $style = ['object-fit' => $objectFit];
        $ratio = null !== $width && null !== $height ? \sprintf('%d / %d', $width, $height) : null;

        switch ($layout) {
            case Layout::Fixed:
                $style += array_filter([
                    'width' => null !== $width ? $width.'px' : null,
                    'height' => null !== $height ? $height.'px' : null,
                ]);
                break;

            case Layout::Constrained:
                $style += array_filter([
                    'max-width' => null !== $width ? $width.'px' : null,
                    'max-height' => null !== $height ? $height.'px' : null,
                    'aspect-ratio' => $ratio,
                ]);
                $style['width'] = '100%';
                // width/height attrs imply a fixed CSS height (browser hint); "height: auto" restores aspect-ratio.
                if (null !== $ratio) {
                    $style['height'] = 'auto';
                }
                break;

            case Layout::FullWidth:
                $style['width'] = '100%';
                if (null !== $ratio) {
                    $style['aspect-ratio'] = $ratio;
                    $style['height'] = 'auto';
                } elseif (null !== $height) {
                    $style['height'] = $height.'px';
                }
                break;
        }

        return $style;
    }
}
