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

/**
 * Default URL adapter that mirrors Symfony's simple path prefixing style.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class GenericUrlAdapter implements UrlAdapterInterface
{
    public function resolve(string $path, array $storageConfig, array $variantConfig = [], ?string $storageName = null): string
    {
        $prefix = $storageConfig['public_url_prefix'] ?? '';

        return (\is_string($prefix) && '' !== $prefix ? rtrim($prefix, '/') : '').'/'.ltrim($path, '/');
    }

    public static function getName(): string
    {
        return 'generic';
    }
}
