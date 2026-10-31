<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests\Fixtures;

/**
 * Shaped like a Doctrine entity: a private property behind a getter, which only PropertyAccess can read.
 */
final class Category
{
    public function __construct(
        private readonly string $name = 'Shoes',
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
