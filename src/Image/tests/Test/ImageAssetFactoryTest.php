<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Test;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Test\ImageAssetFactory;

final class ImageAssetFactoryTest extends TestCase
{
    public function testItCreatesACompleteDeterministicResponsiveAsset()
    {
        $first = ImageAssetFactory::responsive();
        $second = ImageAssetFactory::responsive();

        self::assertSame($first->toArray(), $second->toArray());
        self::assertSame(['webp', 'jpeg'], $first->getAvailableFormats());
        self::assertSame('/fixtures/image-640.webp', $first->getVariant('640w')['path']);
        self::assertSame(400, $first->getVariant('640w')['height']);
        self::assertSame('test_responsive', $first->profile);
    }

    public function testItNormalizesJpgAndAcceptsCustomDimensions()
    {
        $asset = ImageAssetFactory::responsive(
            formats: ['jpg'],
            widths: [300],
            originalWidth: 1200,
            originalHeight: 800,
        );

        self::assertSame(['jpeg'], $asset->getAvailableFormats());
        self::assertSame(200, $asset->getVariant('300w')['height']);
        self::assertSame('image/jpeg', $asset->getVariant('300w')['mimeType']);
    }

    public function testItRejectsInvalidFixtureDefinitions()
    {
        $this->expectException(\InvalidArgumentException::class);

        ImageAssetFactory::responsive(widths: [0]);
    }
}
