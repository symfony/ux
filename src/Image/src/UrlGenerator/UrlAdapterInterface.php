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
 * Allows storages to resolve public URLs or paths for assets and their variants.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface UrlAdapterInterface
{
    /**
     * @param array<string, mixed> $storageConfig
     *                                            Canonical image keys follow ImageSource::toArray(); custom adapter keys
     *                                            are preserved
     * @param array<string, mixed> $variantConfig
     *
     * @throws ExceptionInterface
     */
    public function resolve(string $path, array $storageConfig, array $variantConfig = [], ?string $storageName = null): string;

    /**
     * Return the adapter identifier used in storage configuration.
     */
    public static function getName(): string;
}
