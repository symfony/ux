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
final class TypographyToken extends AbstractToken
{
    public function getType(): string
    {
        return 'typography';
    }

    public function __toString(): string
    {
        return \sprintf(
            '%s %s/%s %s',
            self::member('fontWeight', $this->value['fontWeight'] ?? null),
            self::member('dimension', $this->value['fontSize'] ?? null),
            self::member('number', $this->value['lineHeight'] ?? null),
            self::member('fontFamily', $this->value['fontFamily'] ?? null),
        );
    }
}
