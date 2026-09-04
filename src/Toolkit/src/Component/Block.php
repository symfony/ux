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
 * A block documented by a `{## <description> #}` comment placed directly above the block.
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
