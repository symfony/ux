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
 * @extends AbstractToken<array{value: int|float, unit: string}>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class DurationToken extends AbstractToken
{
    public function getType(): string
    {
        return 'duration';
    }

    public function __toString(): string
    {
        return CssValue::stringify($this->value);
    }
}
