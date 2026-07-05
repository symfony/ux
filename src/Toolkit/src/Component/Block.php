<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Component;

use Symfony\UX\Toolkit\Assert;

/**
 * A block declared in a Twig component's docblock (`{# @block name description #}`).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Block
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
    ) {
        Assert::blockName($this->name);
    }
}
