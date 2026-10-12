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
final class TransitionToken extends AbstractToken
{
    public function getType(): string
    {
        return 'transition';
    }

    public function __toString(): string
    {
        return \sprintf(
            '%s %s %s',
            self::member('duration', $this->value['duration'] ?? null),
            self::member('cubicBezier', $this->value['timingFunction'] ?? null),
            self::member('duration', $this->value['delay'] ?? null),
        );
    }
}
