<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Fixtures;

use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\PathEncoder;
use Symfony\UX\Image\Provider\ProviderInterface;

final class FakeProvider implements ProviderInterface
{
    public function __construct(private readonly bool $autoFormat = true)
    {
    }

    public function getName(): string
    {
        return 'fake';
    }

    public function generateUrl(ImageTransformation $transformation): string
    {
        $path = PathEncoder::encode($transformation->path);
        $params = [];

        if (null !== $transformation->width) {
            $params[] = 'w='.$transformation->width;
        }
        $params[] = 'fm='.($transformation->format ?? '');
        if (null !== $transformation->height) {
            $params[] = 'h='.$transformation->height;
        }

        foreach ($transformation->operations as $key => $value) {
            $params[] = \sprintf('%s=%s', $key, $value);
        }

        return \sprintf('/%s?%s', $path, implode('&', $params));
    }

    public function getSupportedOperations(): array
    {
        return ['sharpen'];
    }

    public function getSupportedFormats(): array
    {
        return ['avif', 'webp', 'jpeg'];
    }

    public function supportsAutoFormat(): bool
    {
        return $this->autoFormat;
    }
}
