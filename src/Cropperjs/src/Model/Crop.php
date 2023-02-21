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

use Intervention\Image\Constraint;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 */
class Crop
{
    private $imageManager;
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
    ];

    public function __construct(ImageManager $imageManager, string $filename)
    {
        $this->imageManager = $imageManager;
        $this->filename = $filename;
    }

    public function getCroppedThumbnail(int $maxWidth, int $maxHeight, string $format = 'jpg', int $quality = 80): string
    {
        $image = $this->createCroppedImage();

        $image->resize($maxWidth, $maxHeight, static function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $image->encode($format, $quality);

        return $image->getEncoded();
    }

    public function getCroppedImage(string $format = 'jpg', int $quality = 80): string
    {
        $image = $this->createCroppedImage();

        // Max size
        if ($this->maxWidth && $this->maxHeight) {
            $image->resize($this->maxWidth, $this->maxHeight, static function (Constraint $constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $image->encode($format, $quality);

        return $image->getEncoded();
    }

    private function createCroppedImage(): Image
    {
        $image = $this->imageManager->make(file_get_contents($this->filename));

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
