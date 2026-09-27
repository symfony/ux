<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image;

use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Provider\NullProvider;
use Symfony\UX\Image\Provider\ProviderInterface;

/**
 * Generates the URL of one transformed image through the active provider.
 *
 * Use it wherever a single URL is needed rather than a srcset: an Open Graph
 * image, an email, an API response or a CSS background.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class ImageUrlGenerator
{
    public function __construct(
        private readonly ProviderInterface $provider,
    ) {
    }

    /**
     * @param array<string, array<string, scalar>> $operations provider-specific operations, keyed by provider name
     *
     * @throws InvalidArgumentException when a value is invalid, or not supported by the active provider
     */
    public function generate(string $src, ?int $width = null, ?int $height = null, ?Fit $fit = null, ?string $format = null, ?int $quality = null, array $operations = []): string
    {
        $fit ??= null !== $width && null !== $height ? Fit::Cover : null;

        if ($this->provider instanceof NullProvider) {
            return $this->provider->generateUrl(new ImageTransformation($src, $width, $height, $fit, $format, $quality));
        }

        if (null !== $format) {
            $this->assertSupportedFormat($format);
        }

        return $this->provider->generateUrl(new ImageTransformation($src, $width, $height, $fit, $format, $quality, $this->resolveOperations($operations)));
    }

    private function assertSupportedFormat(string $format): void
    {
        $supported = $this->provider->getSupportedFormats();

        if ('auto' === $format && $this->provider->supportsAutoFormat()) {
            return;
        }

        if (!\in_array($format, $supported, true)) {
            throw new InvalidArgumentException(\sprintf('The image format "%s" is not supported by the "%s" provider (supported: "%s").', $format, $this->provider->getName(), implode('", "', $supported)));
        }
    }

    /**
     * @param array<string, array<string, scalar>> $operations
     *
     * @return array<string, scalar>
     */
    private function resolveOperations(array $operations): array
    {
        $resolved = $operations[$this->provider->getName()] ?? [];
        if (!\is_array($resolved)) {
            throw new InvalidArgumentException(\sprintf('The "operations.%s" option must be a map of operation names to values, "%s" given.', $this->provider->getName(), get_debug_type($resolved)));
        }

        $supported = $this->provider->getSupportedOperations();

        foreach (array_keys($resolved) as $name) {
            if (!\in_array($name, $supported, true)) {
                throw new InvalidArgumentException(\sprintf('The image operation "%s" is not supported by the "%s" provider (supported: "%s").', $name, $this->provider->getName(), implode('", "', $supported)));
            }
        }

        return $resolved;
    }
}
