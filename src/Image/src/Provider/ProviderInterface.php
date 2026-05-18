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
 * Contract for image transformation providers.
 *
 * Providers generate URLs for transformed images (resized, cropped, converted).
 * Implement this interface to add support for any image CDN or processing library.
 *
 * @author Aleksey Razbakov <aleksey@razbakov.com>
 */
interface ProviderInterface
{
    /**
     * Returns the unique name of this provider.
     */
    public function getName(): string;

    /**
     * Configures the provider with the given options.
     */
    public function configure(array $config): void;

    /**
     * Generates the URL for a transformed image.
     *
     * @param string $src       The source image path (e.g. "/images/hero.jpg")
     * @param array  $modifiers Transformation options (width, height, format, quality, etc.)
     *
     * @return string The URL of the transformed image
     */
    public function getImage(string $src, array $modifiers): string;
}
