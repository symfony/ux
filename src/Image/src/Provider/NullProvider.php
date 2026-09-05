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

use Symfony\UX\Image\Exception\LogicException;
use Symfony\UX\Image\ImageTransformation;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class NullProvider implements ProviderInterface
{
    public function getName(): string
    {
        return 'null';
    }

    public function generateUrl(ImageTransformation $transformation): string
    {
        throw new LogicException('No image provider is configured. Install a bridge such as "symfony/ux-glide-image", "symfony/ux-keycdn-image" or "symfony/ux-cloudflare-image".');
    }

    public function getSupportedOperations(): array
    {
        return [];
    }

    public function getSupportedFormats(): array
    {
        return [];
    }

    public function supportsAutoFormat(): bool
    {
        return true;
    }
}
