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
 * A DTCG dimension: a number with a px or rem unit (Format 8.2).
 *
 * @extends AbstractToken<array{value: int|float, unit: string}>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class DimensionToken extends AbstractToken
{
    public function getType(): string
    {
        return 'dimension';
    }

    public function __toString(): string
    {
        return CssValue::stringify($this->value);
    }
}
