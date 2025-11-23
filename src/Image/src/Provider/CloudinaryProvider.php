<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Provider;

/**
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
final class CloudinaryProvider implements ProviderInterface
{
    private string $baseUrl = '';
    private array $defaults = [];

    private const KEY_MAP = [
        'width' => 'w',
        'height' => 'h',
        'format' => 'f',
        'quality' => 'q',
        'fit' => 'c',
        'focal' => 'g',
        'background' => 'b',
        'ratio' => 'ar',
        'roundCorner' => 'r',
        'gravity' => 'g',
        'rotate' => 'a',
        'effect' => 'e',
        'color' => 'co',
        'flags' => 'fl',
        'dpr' => 'dpr',
        'opacity' => 'o',
        'overlay' => 'l',
        'underlay' => 'u',
        'transformation' => 't',
        'zoom' => 'z',
        'colorSpace' => 'cs',
        'customFunc' => 'fn',
        'density' => 'dn',
        'aspectRatio' => 'ar',
        'blur' => 'e_blur',
    ];

    private const VALUE_MAP = [
        'fit' => [
            'fill' => 'fill',
            'inside' => 'pad',
            'outside' => 'lpad',
            'cover' => 'lfill',
            'contain' => 'scale',
            'minCover' => 'mfit',
            'minInside' => 'mpad',
            'thumbnail' => 'thumb',
            'cropping' => 'crop',
            'coverLimit' => 'limit',
        ],
        'format' => [
            'jpeg' => 'jpg',
        ],
        'gravity' => [
            'auto' => 'auto',
            'subject' => 'auto:subject',
            'face' => 'face',
            'sink' => 'sink',
            'faceCenter' => 'face:center',
            'multipleFaces' => 'faces',
            'multipleFacesCenter' => 'faces:center',
            'north' => 'north',
            'northEast' => 'north_east',
            'northWest' => 'north_west',
            'west' => 'west',
            'southWest' => 'south_west',
            'south' => 'south',
            'southEast' => 'south_east',
            'east' => 'east',
            'center' => 'center',
        ],
    ];

    public function configure(array $config): void
    {
        $this->baseUrl = $config['base_url'] ?? '';
        $this->defaults = $config['defaults'] ?? [];
    }

    public function getName(): string
    {
        return 'cloudinary';
    }

    public function getImage(string $src, array $modifiers): string
    {
        // Remove any leading slashes
        $src = ltrim($src, '/');

        // Check if the source is an external URL
        if (str_starts_with($src, 'http://') || str_starts_with($src, 'https://')) {
            if (str_contains($src, '/upload/')) {
                // Extract the domain part up to /upload/
                $this->baseUrl = substr($src, 0, strpos($src, '/upload/') + 8);
                // Extract the path after /upload/
                $src = substr($src, strpos($src, '/upload/') + 8);
            }
        }

        $transformations = [];

        // Merge defaults with provided modifiers
        $modifiers = array_merge($this->defaults, $modifiers);

        // Define the order of transformations
        $orderPriority = [
            'width' => 1,
            'height' => 2,
            'quality' => 3,
            'format' => 4,
            // Add other keys with their priority if needed
        ];

        // Process modifiers and store them with their priority
        $prioritizedTransformations = [];
        foreach ($modifiers as $key => $value) {
            if (!isset(self::KEY_MAP[$key])) {
                continue;
            }

            $cloudinaryKey = self::KEY_MAP[$key];

            // Handle special value mappings
            if (isset(self::VALUE_MAP[$key])) {
                if (isset(self::VALUE_MAP[$key][$value])) {
                    $value = self::VALUE_MAP[$key][$value];
                }
            }

            // Rest of the switch case for special handling...
            switch ($key) {
                case 'background':
                case 'color':
                    $value = $this->convertHexToRgb($value);
                    break;
                case 'roundCorner':
                    if ('max' === $value) {
                        $value = 'max';
                    } elseif (str_contains($value, ':')) {
                        $value = str_replace(':', '_', $value);
                    }
                    break;
                case 'blur':
                    $cloudinaryKey = 'e';
                    $value = 'blur:'.$value;
                    break;
            }

            // Format the transformation
            $transformation = str_contains($cloudinaryKey, '_')
                ? $cloudinaryKey.':'.$value
                : $cloudinaryKey.'_'.$value;

            // Store with priority if defined, otherwise use a high number
            $priority = $orderPriority[$key] ?? 999;
            $prioritizedTransformations[$priority] = $transformation;
        }

        // Sort by priority and get final transformations
        ksort($prioritizedTransformations);
        $transformations = array_values($prioritizedTransformations);

        // Build the final URL
        $transformationString = implode(',', $transformations);

        return \sprintf(
            '%s/%s%s',
            rtrim($this->baseUrl, '/'),
            $transformationString ? $transformationString.'/' : '',
            $src
        );
    }

    private function convertHexToRgb(string $value): string
    {
        return str_starts_with($value, '#') ? 'rgb_'.ltrim($value, '#') : $value;
    }
}
