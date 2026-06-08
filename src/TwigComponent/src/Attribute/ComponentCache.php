<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Attribute;

/**
 * Marks a method as being cached persistently across requests.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class ComponentCache
{
    /**
     * @param string|null     $key          The cache key. If null, an automatic key will be generated.
     * @param int|string|null $expiresAfter the TTL (time to live) in seconds or a DateInterval string
     * @param array<string>   $tags         an array of cache tags (if using TagAwareCacheInterface)
     * @param string|null     $pool         the cache pool service name to use
     */
    public function __construct(
        public readonly ?string $key = null,
        public readonly int|string|null $expiresAfter = null,
        public readonly array $tags = [],
        public readonly ?string $pool = null,
    ) {
    }
}
