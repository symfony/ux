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
 * Default provider that returns the original image path without transformations.
 *
 * Works with local files out of the box — no CDN or image processing library required.
 *
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
class PassThroughProvider implements ProviderInterface
{
    private string $assetsPath = '';

    public function getName(): string
    {
        return 'passthrough';
    }

    public function configure(array $config): void
    {
        $this->assetsPath = $config['assets_path'] ?? '';
    }

    public function getImage(string $src, array $modifiers): string
    {
        // Return the original path — no transformations
        if ('' !== $this->assetsPath && !str_starts_with($src, '/')) {
            return rtrim($this->assetsPath, '/').'/'.$src;
        }

        return $src;
    }
}
