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

use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Fit;
use Symfony\UX\Image\ImagePresets;

final class ImagePresetsTest extends TestCase
{
    private const array NO_OPTIONS = [
        'width' => null,
        'height' => null,
        'fit' => null,
        'format' => null,
        'quality' => null,
        'operations' => [],
    ];

    public function testThePresetFillsEveryOptionLeftToNull(): void
    {
        $presets = new ImagePresets([
            'thumbnail' => ['width' => 200, 'height' => 150, 'fit' => 'contain', 'format' => 'webp', 'quality' => 70],
        ]);

        $options = $presets->merge('thumbnail', self::NO_OPTIONS);

        $expected = ['width' => 200, 'height' => 150, 'fit' => Fit::Contain, 'format' => 'webp', 'quality' => 70, 'operations' => []];
        self::assertSame($expected, $options);
    }

    public function testAGivenOptionWinsOverThePreset(): void
    {
        $presets = new ImagePresets(['thumbnail' => ['width' => 200, 'quality' => 70]]);

        $options = $presets->merge('thumbnail', ['width' => 300] + self::NO_OPTIONS);

        self::assertSame(300, $options['width']);
        self::assertSame(70, $options['quality']);
    }

    public function testOperationsAreMergedPerProviderAndPerOperation(): void
    {
        $presets = new ImagePresets(['vintage' => ['operations' => ['fake' => ['sharpen' => 2, 'blur' => 1]]]]);
        $given = ['fake' => ['sharpen' => 5], 'other' => ['gravity' => 'auto']];

        $options = $presets->merge('vintage', ['operations' => $given] + self::NO_OPTIONS);

        $expected = ['fake' => ['sharpen' => 5, 'blur' => 1], 'other' => ['gravity' => 'auto']];
        self::assertSame($expected, $options['operations']);
    }

    public function testANullOperationKeepsThePresetValue(): void
    {
        $presets = new ImagePresets(['vintage' => ['operations' => ['fake' => ['sharpen' => 2, 'blur' => 1]]]]);

        $options = $presets->merge('vintage', ['operations' => ['fake' => ['sharpen' => null]]] + self::NO_OPTIONS);

        self::assertSame(['fake' => ['sharpen' => 2, 'blur' => 1]], $options['operations']);
    }

    public function testANullProviderMapKeepsThePresetOperations(): void
    {
        $presets = new ImagePresets(['vintage' => ['operations' => ['fake' => ['sharpen' => 2]]]]);

        $options = $presets->merge('vintage', ['operations' => ['fake' => null]] + self::NO_OPTIONS);

        self::assertSame(['fake' => ['sharpen' => 2]], $options['operations']);
    }

    public function testAFitCanBeGivenAsAnEnum(): void
    {
        $presets = new ImagePresets(['boxed' => ['fit' => Fit::Contain]]);

        $options = $presets->merge('boxed', self::NO_OPTIONS);

        self::assertSame(Fit::Contain, $options['fit']);
    }

    public function testAnInvalidFitIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "fit" value "bogus": expected one of "cover", "contain".');

        new ImagePresets(['boxed' => ['fit' => 'bogus']]);
    }

    public function testAnUnknownPresetOptionIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown option "widht" in the "thumbnail" image preset: expected one of "width", "height", "fit", "format", "quality", "operations".');

        new ImagePresets(['thumbnail' => ['widht' => 200]]);
    }

    public function testAPresetThatIsNotAnArrayIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "og" image preset must be an array, "null" given.');

        new ImagePresets(['og' => null]);
    }

    public function testAnInvalidPresetOptionTypeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "width" option of the "thumbnail" image preset must be of type "int", "string" given.');

        new ImagePresets(['thumbnail' => ['width' => '200']]);
    }

    public function testPresetOperationsMustBeAMapOfProviderMaps(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "operations" option of the "og" image preset must map provider names to operation maps.');

        new ImagePresets(['og' => ['operations' => 'x']]);
    }

    public function testAnUnknownPresetNamesTheDefinedOnes(): void
    {
        $presets = new ImagePresets(['thumbnail' => [], 'og' => []]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image preset "thumbnial" does not exist (defined: "thumbnail", "og").');

        $presets->merge('thumbnial', self::NO_OPTIONS);
    }

    public function testAnUnknownPresetSaysWhenNoneIsDefined(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The image preset "thumbnail" does not exist, no preset is defined under "ux_image.presets".');

        new ImagePresets([])->merge('thumbnail', self::NO_OPTIONS);
    }
}
