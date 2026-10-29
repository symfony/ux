<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests\Fixtures;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

final class EntityMetadataCacheWarmer implements CacheWarmerInterface
{
    public function __construct(
        private EntityMetadataRegistry $registry,
    ) {
    }

    public function isOptional(): bool
    {
        return true;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        if ($this->registry->isLoaded()) {
            throw new \LogicException('The entity metadata must be loaded by this cache warmer first.');
        }

        $this->registry->load();

        return [];
    }
}
