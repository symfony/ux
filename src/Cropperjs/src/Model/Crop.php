<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Cropperjs\Model;

use Intervention\Image\ImageManager;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\Cropperjs\Intervention\InterventionImage;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 */
class Crop
{
    private ImageManager $imageManager;
    private $filename;

    /**
     * @var int|null
     */
    private $maxWidth;

    /**
     * @var int|null
     */
    private $maxHeight;

    /**
     * @Assert\NotBlank()
     *
     * @Assert\Type("array")
     */
    private $options = [
        'x' => 0,
        'y' => 0,
        'width' => null,
        'height' => null,
        'rotate' => 0,
    ];

    public function __construct(ImageManager $imageManager, string $filename)
    {
        $this->imageManager = $imageManager;
        $this->filename = $filename;
    }

    public function getCroppedThumbnail(int $maxWidth, int $maxHeight, string $format = 'jpg', int $quality = 80): string
    {
        $image = $this->createCroppedImage();

        $this->scaleDown($image, $maxWidth, $maxHeight);
        $this->rotate($image);

        return $this->encode($image, $format, $quality);
    }

    public function getCroppedImage(string $format = 'jpg', int $quality = 80): string
    {
        $image = $this->createCroppedImage();

        // Max size
        if ($this->maxWidth && $this->maxHeight) {
            $this->scaleDown($image, $this->maxWidth, $this->maxHeight);
        }

        $this->rotate($image);

        return $this->encode($image, $format, $quality);
    }

    /**
     * @return \Intervention\Image\Image|\Intervention\Image\Interfaces\ImageInterface
     */
    private function createCroppedImage(): object
    {
        $binary = file_get_contents($this->filename);

        $image = match (InterventionImage::major()) {
            InterventionImage::V4 => $this->imageManager->decodeBinary($binary),
            InterventionImage::V3 => $this->imageManager->read($binary),
            default => $this->imageManager->make($binary),
        };

        // Crop
        if ($this->options['width'] && $this->options['height']) {
            $image->crop(
                (int) round($this->options['width']),
                (int) round($this->options['height']),
                (int) round($this->options['x']),
                (int) round($this->options['y'])
            );
        }

        return $image;
    }

    private function scaleDown(object $image, int $maxWidth, int $maxHeight): void
    {
        if (InterventionImage::V2 === InterventionImage::major()) {
            $image->resize($maxWidth, $maxHeight, static function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            return;
        }

        $image->scaleDown($maxWidth, $maxHeight);
    }

    private function rotate(object $image): void
    {
        if (empty($this->options['rotate'])) {
            return;
        }

        // v2 and v3 hand the angle straight to imagerotate(), which turns counter-clockwise, while v4
        // negates it internally and so already turns clockwise like cropper.js does
        $image->rotate(
            InterventionImage::V4 === InterventionImage::major()
                ? $this->options['rotate']
                : -1 * $this->options['rotate']
        );
    }

    private function encode(object $image, string $format, int $quality): string
    {
        return match (InterventionImage::major()) {
            InterventionImage::V4 => (string) $image->encodeUsingFileExtension($format, quality: $quality),
            InterventionImage::V3 => (string) $image->encodeByExtension($format, quality: $quality),
            default => (string) $image->encode($format, $quality)->getEncoded(),
        };
    }

    public function getOptions(): string
    {
        return json_encode($this->options);
    }

    /**
     * @return $this
     */
    public function setOptions(string $options): self
    {
        $this->options = json_decode($options, true);

        return $this;
    }

    /**
     * @return $this
     */
    public function setDefaultOptions(array $options): self
    {
        foreach ($this->options as $key => $defaultValue) {
            if (isset($options[$key])) {
                $this->options[$key] = $options[$key];
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function setCroppedMaxSize(int $maxWidth, int $maxHeight): self
    {
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;

        return $this;
    }
}
