<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\ImageSource;
use Symfony\UX\Image\ImageSourceSet;

#[CoversClass(ImageSourceSet::class)]
final class ImageSourceSetTest extends TestCase
{
    public function testEmptySourceSet(): void
    {
        foreach ([null, []] as $raw) {
            $sourceSet = ImageSourceSet::fromArray($raw);

            self::assertTrue($sourceSet->isEmpty());
            self::assertFalse($sourceSet->isMultiRatio());
            self::assertSame([], $sourceSet->getAvailableFormats());
            self::assertSame([], $sourceSet->toArray());
        }
    }

    public function testUsesCanonicalFormatShape(): void
    {
        $sourceSet = ImageSourceSet::fromArray([
            'webp' => [
                self::variant('/img/small.webp', 'small', 'webp', 640),
                self::variant('/img/large.webp', 'large', 'webp', 1280),
            ],
            'jpeg' => [
                self::variant('/img/small.jpeg', 'small', 'jpeg', 640),
            ],
        ]);

        self::assertSame(['webp', 'jpeg'], $sourceSet->getAvailableFormats());
        self::assertCount(2, $sourceSet->getForFormat('webp'));
        self::assertInstanceOf(ImageSource::class, $sourceSet->getPrimaryForFormat('webp'));
        self::assertSame('/img/small.webp 640w, /img/large.webp 1280w', $sourceSet->buildSrcset('webp'));
        self::assertSame([], $sourceSet->getForFormat('png'));
        self::assertNull($sourceSet->getPrimaryForFormat('png'));
        self::assertNull($sourceSet->buildSrcset('png'));
        self::assertSame(['webp', 'jpeg'], array_keys($sourceSet->getSingleRatioFormats()));
    }

    public function testArtDirectionIsDerivedFromVariantMedia(): void
    {
        $sourceSet = ImageSourceSet::fromArray([
            'webp' => [
                self::variant('/img/mobile.webp', 'mobile', 'webp', 640, '(max-width: 40rem)'),
                self::variant('/img/desktop.webp', 'desktop', 'webp', 1280, '(min-width: 40.001rem)'),
            ],
            'jpeg' => [
                self::variant('/img/mobile.jpeg', 'mobile', 'jpeg', 640, '(max-width: 40rem)'),
            ],
        ]);

        self::assertTrue($sourceSet->isMultiRatio());
        $groups = $sourceSet->getMultiRatioGroups();
        self::assertCount(2, $groups);
        self::assertSame('(max-width: 40rem)', $groups[0]['media']);
        self::assertArrayHasKey('webp', $groups[0]['formats']);
        self::assertArrayHasKey('jpeg', $groups[0]['formats']);
        self::assertSame('(min-width: 40.001rem)', $groups[1]['media']);
    }

    public function testFallbackGroupIsOrderedLast(): void
    {
        $fallback = self::variant('/img/fallback.webp', 'fallback', 'webp', 640);
        $directed = self::variant('/img/mobile.webp', 'mobile', 'webp', 640, '(max-width: 40rem)');
        $groups = ImageSourceSet::fromArray(['webp' => [$fallback, $directed]])->getMultiRatioGroups();

        self::assertSame('(max-width: 40rem)', $groups[0]['media']);
        self::assertNull($groups[1]['media']);
    }

    public function testRejectsEmptyFormatKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ImageSourceSet::fromArray(['' => [self::variant('/img/photo.webp', 'photo', 'webp', 640)]]);
    }

    public function testDensityDescriptorTakesPrecedenceOverWidth(): void
    {
        $variant = self::variant('/img/retina.webp', 'retina', 'webp', 1280);
        $variant['density'] = '2x';
        $sourceSet = ImageSourceSet::fromArray(['webp' => [$variant]]);

        self::assertSame('/img/retina.webp 2x', $sourceSet->buildSrcset('webp'));
    }

    public function testDuplicateDescriptorsCollapseToLastCandidate(): void
    {
        $sourceSet = ImageSourceSet::fromArray([
            'webp' => [
                self::variant('/img/old.webp', 'old', 'webp', 640),
                self::variant('/img/current.webp', 'current', 'webp', 640),
            ],
        ]);

        self::assertSame('/img/current.webp 640w', $sourceSet->buildSrcset('webp'));
    }

    public function testRejectsDescriptorSetsThatCannotBeNormalized(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must all expose width descriptors or all expose density descriptors');

        ImageSourceSet::fromArray([
            'webp' => [
                ['path' => '/img/bare.webp'],
                ['path' => '/img/retina.webp', 'density' => '2x'],
            ],
        ]);
    }

    public function testVariantWithoutDescriptorUsesTheBarePath(): void
    {
        $sourceSet = ImageSourceSet::fromArray([
            'webp' => [['path' => '/img/original.webp']],
        ]);

        self::assertSame('/img/original.webp', $sourceSet->buildSrcset('webp'));
    }

    public function testRoundTripKeepsCompleteVariantMetadata(): void
    {
        $raw = ['webp' => [self::variant('/img/card.webp', 'card', 'webp', 600, null, 'crop', 86, '30% 60%')]];

        self::assertEquals($raw, ImageSourceSet::fromArray($raw)->toArray());
    }

    public function testRejectsLegacyTopLevelArtDirectionShape(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('format => list<variant>');

        ImageSourceSet::fromArray([
            ['media' => '(max-width: 40rem)', 'formats' => ['webp' => [self::variant('/img/mobile.webp', 'mobile', 'webp', 640)]]],
        ]);
    }

    public function testRejectsLegacyVariantNameShape(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('non-empty list');

        ImageSourceSet::fromArray([
            'thumbnail' => ['webp' => self::variant('/img/thumb.webp', 'thumbnail', 'webp', 300)],
        ]);
    }

    public function testRejectsMalformedVariantsInsteadOfSkippingThem(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('webp[1]');

        ImageSourceSet::fromArray([
            'webp' => [
                self::variant('/img/valid.webp', 'valid', 'webp', 640),
                'malformed',
            ],
        ]);
    }

    public function testRejectsMismatchedEmbeddedFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('declares format "jpeg"');

        ImageSourceSet::fromArray([
            'webp' => [self::variant('/img/photo.jpeg', 'photo', 'jpeg', 640)],
        ]);
    }

    /**
     * @return array<string, int|string|null>
     */
    private static function variant(
        string $path,
        string $name,
        string $format,
        int $width,
        ?string $media = null,
        string $mode = 'fit',
        int $quality = 80,
        string $position = 'center',
    ): array {
        return [
            'name' => $name,
            'path' => $path,
            'format' => $format,
            'mimeType' => 'image/'.$format,
            'width' => $width,
            'height' => (int) round($width * 0.75),
            'mode' => $mode,
            'quality' => $quality,
            'position' => $position,
            'density' => null,
            'media' => $media,
        ];
    }
}
