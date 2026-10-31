<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Engine\Css;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Root extends Container
{
    public function toString(): string
    {
        return $this->childrenToString();
    }

    public function toCompactString(): string
    {
        return $this->childrenToCompactString();
    }
}
