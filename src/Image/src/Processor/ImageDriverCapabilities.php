<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Processor;

use Symfony\UX\Image\Exception\ImageProcessingException;

final readonly class ImageDriverCapabilities
{
    /** @var list<string> */
    public array $encodableFormats;

    /** @param list<string> $encodableFormats */
    public function __construct(array $encodableFormats)
    {
        $normalized = [];
        foreach ($encodableFormats as $format) {
            $format = 'jpg' === strtolower(trim($format)) ? 'jpeg' : strtolower(trim($format));
            if ('' === $format) {
                throw new \Symfony\UX\Image\Exception\InvalidArgumentException('Driver codec names must not be empty.');
            }
            $normalized[$format] = true;
        }
        if ([] === $normalized) {
            throw new \Symfony\UX\Image\Exception\InvalidArgumentException('An image driver must expose at least one encodable format.');
        }

        $this->encodableFormats = array_keys($normalized);
    }

    public static function gd(): self
    {
        $formats = ['jpeg', 'png'];
        if (\function_exists('imagewebp')) {
            $formats[] = 'webp';
        }
        if (\function_exists('imageavif')) {
            $formats[] = 'avif';
        }

        return new self($formats);
    }

    /** @param list<string> $formats */
    public function assertEncodable(array $formats, string $profile): void
    {
        foreach ($formats as $format) {
            $normalized = 'jpg' === $format ? 'jpeg' : $format;
            if (!\in_array($normalized, $this->encodableFormats, true)) {
                throw ImageProcessingException::processingFailed('profile validation', \sprintf('Profile "%s" requests unavailable codec "%s". Available codecs: %s.', $profile, $format, implode(', ', $this->encodableFormats)));
            }
        }
    }
}
