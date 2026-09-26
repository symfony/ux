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

/**
 * A DTCG font weight, printed as a number: CSS knows two of the DTCG keywords.
 *
 * @extends AbstractToken<int|string>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class FontWeightToken extends AbstractToken
{
    /** The DTCG keyword aliases, per Format 8.4. */
    private const WEIGHTS = [
        'thin' => 100, 'hairline' => 100,
        'extra-light' => 200, 'ultra-light' => 200,
        'light' => 300,
        'normal' => 400, 'regular' => 400, 'book' => 400,
        'medium' => 500,
        'semi-bold' => 600, 'demi-bold' => 600,
        'bold' => 700,
        'extra-bold' => 800, 'ultra-bold' => 800,
        'black' => 900, 'heavy' => 900, 'extra-black' => 950, 'ultra-black' => 950,
    ];

    public function getType(): string
    {
        return 'fontWeight';
    }

    public function __toString(): string
    {
        if (\is_string($this->value) && isset(self::WEIGHTS[strtolower($this->value)])) {
            return (string) self::WEIGHTS[strtolower($this->value)];
        }

        return (string) $this->value;
    }
}
