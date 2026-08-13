<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Profile;

use Symfony\UX\Image\Exception\UnknownImageProfileException;
use Symfony\UX\Image\Transformation\FocalPoint;
use Symfony\UX\Image\Transformation\ResizeMode;

final class ProfileRegistry
{
    /** @var array<string, ImageProfile> */
    private array $profiles = [];

    /** @param array<string, array<string, mixed>> $profiles */
    public function __construct(array $profiles)
    {
        foreach ($profiles as $name => $configuration) {
            $variants = [];
            $configuredVariants = $configuration['variants'] ?? [];
            if (!\is_array($configuredVariants)) {
                throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Variants for profile "%s" must be an array.', $name));
            }
            foreach ($configuredVariants as $variantName => $variant) {
                if (!\is_string($variantName) || !\is_array($variant)) {
                    continue;
                }
                $variants[$variantName] = new VariantDefinition(
                    $variantName,
                    \is_int($variant['width'] ?? null) ? $variant['width'] : null,
                    \is_int($variant['height'] ?? null) ? $variant['height'] : null,
                    ResizeMode::from(\is_string($variant['mode'] ?? null) ? $variant['mode'] : 'fit'),
                    \is_int($variant['quality'] ?? null) ? $variant['quality'] : 80,
                    FocalPoint::fromString(\is_string($variant['position'] ?? null) ? $variant['position'] : 'center'),
                    \is_string($variant['media'] ?? null) ? $variant['media'] : null,
                    \is_string($variant['density'] ?? null) ? $variant['density'] : null,
                );
            }
            $configuredFormats = $configuration['formats'] ?? [];
            if (!\is_array($configuredFormats)) {
                throw new \Symfony\UX\Image\Exception\InvalidArgumentException(\sprintf('Formats for profile "%s" must be an array.', $name));
            }
            $formats = [];
            foreach ($configuredFormats as $format) {
                if (\is_string($format)) {
                    $formats[] = $format;
                }
            }
            $this->profiles[$name] = new ImageProfile($name, $formats, $variants, ProcessingMode::fromProfile($configuration), $configuration);
        }
    }

    public function get(string $name): ImageProfile
    {
        return $this->profiles[$name] ?? throw UnknownImageProfileException::create($name, array_keys($this->profiles));
    }

    public function has(string $name): bool
    {
        return isset($this->profiles[$name]);
    }
}
