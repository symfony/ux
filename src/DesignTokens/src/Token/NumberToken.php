<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Token;

use Symfony\UX\DesignTokens\Token\Css\CssValue;

/**
 * @extends AbstractToken<int|float>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class NumberToken extends AbstractToken
{
    public function getType(): string
    {
        return 'number';
    }

    public function __toString(): string
    {
        return CssValue::number($this->value);
    }
}
