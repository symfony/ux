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
final class PreloadManager
{
    private array $preloadImages = [];

    public function addPreloadImage(string $src, array $options = []): void
    {
        $this->preloadImages[] = [
            'src' => $src,
            'options' => $options,
        ];
    }

    public function getPreloadTags(): string
    {
        if (empty($this->preloadImages)) {
            return '';
        }

        $tags = [];

        foreach ($this->preloadImages as $image) {
            $src = $image['src'];
            $options = $image['options'];

            // If we have srcset/sizes, create appropriate preload tag
            if (!empty($options['srcset'])) {
                $tags[] = \sprintf(
                    '<link rel="preload" as="image" href="%s" imagesrcset="%s"%s>',
                    $src,
                    $options['srcset'],
                    !empty($options['sizes']) ? ' imagesizes="' . $options['sizes'] . '"' : ''
                );
            } else {
                // Simple preload for single image
                $tags[] = \sprintf(
                    '<link rel="preload" as="image" href="%s">',
                    $src
                );
            }
        }

        return implode("\n", $tags);
    }

    public function reset(): void
    {
        $this->preloadImages = [];
    }
}
