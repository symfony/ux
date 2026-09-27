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

use Symfony\Component\HttpFoundation\AcceptHeader;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class FormatNegotiator
{
    /**
     * @param list<string> $supportedFormats
     */
    public function negotiate(?string $acceptHeader, array $supportedFormats, string $fallback = 'jpeg'): string
    {
        if (null !== $acceptHeader) {
            $items = AcceptHeader::fromString($acceptHeader)->all();

            // Wildcards are ignored on purpose: an older Safari sends "image/*" and cannot display AVIF.
            foreach ($supportedFormats as $format) {
                $item = $items['image/'.$format] ?? null;
                if (null !== $item && $item->getQuality() > 0) {
                    return $format;
                }
            }
        }

        return $fallback;
    }
}
