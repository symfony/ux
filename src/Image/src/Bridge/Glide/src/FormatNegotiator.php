<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Glide;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class FormatNegotiator
{
    /**
     * @param list<string> $supportedFormats
     */
    public function negotiate(?string $acceptHeader, array $supportedFormats, string $fallback = 'jpg'): string
    {
        if (null !== $acceptHeader) {
            foreach ($supportedFormats as $format) {
                if (str_contains($acceptHeader, 'image/'.$format)) {
                    return $format;
                }
            }
        }

        return $fallback;
    }
}
