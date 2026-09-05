<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\UrlGenerator;

use Symfony\UX\Image\Exception\ExceptionInterface;

/**
 * Interface for CDN URL builders.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface CdnUrlBuilderInterface
{
    /**
     * Build a CDN URL with transformations.
     *
     * @param array<string, mixed> $profileConfig
     *                                            Canonical image keys follow ImageSource::toArray(); custom profile and
     *                                            adapter keys are preserved
     * @param array<string, mixed> $variantConfig
     *
     * @throws ExceptionInterface
     */
    public function buildUrl(string $baseUrl, string $path, array $profileConfig, array $variantConfig): string;

    /**
     * Get the provider name.
     */
    public static function getProviderName(): string;
}
