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
 * @extends AbstractToken<string|list<string>>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class FontFamilyToken extends AbstractToken
{
    private const CSS_WIDE_KEYWORDS = ['inherit', 'initial', 'unset', 'revert', 'revert-layer', 'default'];

    public function getType(): string
    {
        return 'fontFamily';
    }

    public function __toString(): string
    {
        $names = \is_array($this->value) ? $this->value : [$this->value];

        return implode(', ', array_map(self::family(...), $names));
    }

    private static function family(string $name): string
    {
        $words = explode(' ', $name);
        $identifiers = array_filter($words, static fn (string $word): bool => 1 === preg_match('/^-?[A-Za-z_][A-Za-z0-9_-]*$/D', $word));
        if (\count($identifiers) === \count($words) && !\in_array(strtolower($name), self::CSS_WIDE_KEYWORDS, true)) {
            return $name;
        }

        return CssValue::string($name);
    }
}
