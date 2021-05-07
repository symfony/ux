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

use Intervention\Image\ImageManager;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 * @experimental
 */
class BlurHash implements BlurHashInterface
{
    private $imageManager;

    public function __construct(ImageManager $imageManager = null)
    {
        $this->imageManager = $imageManager;
    }

    public function createDataUriThumbnail(string $filename, int $width, int $height, int $encodingWidth = 75, int $encodingHeight = 75): string
    {
        // Resize and encode
        $encoded = $this->encode($filename, $encodingWidth, $encodingHeight);

        // Create a new blurred thumbnail from encoded BlurHash
        $pixels = \kornrunner\Blurhash\Blurhash::decode($encoded, $width, $height);

        $thumbnail = $this->imageManager->canvas($width, $height);
        for ($y = 0; $y < $height; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                $thumbnail->pixel($pixels[$y][$x], $x, $y);
            }
        }

        return 'data:image/jpeg;base64,'.base64_encode($thumbnail->encode('jpg', 80));
    }

    public function encode(string $filename, int $encodingWidth = 75, int $encodingHeight = 75): string
    {
        if (!$this->imageManager) {
            throw new \LogicException("Missing package, to use the \"blur_hash\" Twig function, run:\n\ncomposer require intervention/image");
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

        return \kornrunner\Blurhash\Blurhash::encode($pixels, 4, 3);
    }
}
