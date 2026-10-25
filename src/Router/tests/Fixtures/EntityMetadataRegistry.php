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

final class EntityMetadataRegistry
{
    private bool $loaded = false;

    public function load(): void
    {
        $this->loaded = true;
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }
}
