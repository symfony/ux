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
 * @extends AbstractToken<array<string, mixed>>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class BorderToken extends AbstractToken
{
    public function getType(): string
    {
        return 'border';
    }

    public function __toString(): string
    {
        return \sprintf(
            '%s %s %s',
            self::member('dimension', $this->value['width'] ?? null),
            self::member('strokeStyle', $this->value['style'] ?? null),
            self::member('color', $this->value['color'] ?? null),
        );
    }
}
