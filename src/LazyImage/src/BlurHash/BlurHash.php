<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LazyImage\BlurHash;

use Intervention\Image\Colors\Rgb\Color;
use Intervention\Image\Drivers\Gd\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Intervention\Image\ImageManagerStatic;
use kornrunner\Blurhash\Blurhash as BlurhashEncoder;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 */
class BlurHash implements BlurHashInterface
{
    public function __construct(
        private ?ImageManager $imageManager = null,
        private ?CacheInterface $cache = null,
    ) {
    }

    public static function intervention3(): bool
    {
        return !class_exists(ImageManagerStatic::class);
    }

    public function createDataUriThumbnail(string $filename, int $width, int $height, int $encodingWidth = 75, int $encodingHeight = 75): string
    {
        // Resize and encode
        $encoded = $this->encode($filename, $encodingWidth, $encodingHeight);

        // Create a new blurred thumbnail from encoded BlurHash
        $pixels = BlurhashEncoder::decode($encoded, $width, $height);

        return $this->encodeImage($pixels, $width, $height);
    }

    public function encode(string $filename, int $encodingWidth = 75, int $encodingHeight = 75): string
    {
        if ($this->cache) {
            return $this->cache->get(
                'blurhash.'.hash('xxh3', $filename.$encodingWidth.$encodingHeight),
                fn () => $this->doEncode($filename, $encodingWidth, $encodingHeight)
            );
        }

        return $this->doEncode($filename, $encodingWidth, $encodingHeight);
    }

    private function doEncode(string $filename, int $encodingWidth = 75, int $encodingHeight = 75): string
    {
        if (!$this->imageManager) {
            throw new \LogicException('To use the Blurhash feature, install intervention/image.');
        }

        if (!class_exists(BlurhashEncoder::class)) {
            throw new \LogicException('To use the Blurhash feature, install kornrunner/blurhash.');
        }

        return BlurhashEncoder::encode($this->generatePixels($filename, $encodingWidth, $encodingHeight), 4, 3);
    }

    private function generatePixels(string $filename, int $encodingWidth, int $encodingHeight): array
    {
        if (self::intervention3()) {
            $image = $this->imageManager->read($filename)->scale($encodingWidth, $encodingHeight);
            $width = $image->width();
            $height = $image->height();
            $pixels = [];

            for ($y = 0; $y < $height; ++$y) {
                $row = [];
                for ($x = 0; $x < $width; ++$x) {
                    $row[] = $image->pickColor($x, $y)->toArray();
                }

                $pixels[] = $row;
            }

            return $pixels;
        }

        // Resize image to increase encoding performance
        $image = $this->imageManager->make(file_get_contents($filename));
        $image->resize($encodingWidth, $encodingHeight, static function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        // Encode using BlurHash
        $width = $image->getWidth();
        $height = $image->getHeight();
        $pixels = [];

        for ($y = 0; $y < $height; ++$y) {
            $row = [];
            for ($x = 0; $x < $width; ++$x) {
                $color = $image->pickColor($x, $y);
                $row[] = [$color[0], $color[1], $color[2]];
            }

            $pixels[] = $row;
        }

        return $pixels;
    }

    private function encodeImage(array $pixels, int $width, int $height): string
    {
        if (self::intervention3()) {
            $thumbnail = $this->imageManager->create($width, $height);

            for ($y = 0; $y < $height; ++$y) {
                for ($x = 0; $x < $width; ++$x) {
                    $thumbnail->drawPixel($x, $y, new Color($pixels[$y][$x][0], $pixels[$y][$x][1], $pixels[$y][$x][2]));
                }
            }

            return $thumbnail->encode(new JpegEncoder(80))->toDataUri();
        }

        $thumbnail = $this->imageManager->canvas($width, $height);

        for ($y = 0; $y < $height; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                $thumbnail->pixel($pixels[$y][$x], $x, $y);
            }
        }

        return 'data:image/jpeg;base64,'.base64_encode($thumbnail->encode('jpg', 80));
    }
}
