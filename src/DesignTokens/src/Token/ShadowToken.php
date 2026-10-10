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
 * @extends AbstractToken<array<string, mixed>|list<array<string, mixed>>>
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ShadowToken extends AbstractToken
{
    public function getType(): string
    {
        return 'shadow';
    }

    public function __toString(): string
    {
        /** @var list<array<string, mixed>> $shadows */
        $shadows = isset($this->value['color']) ? [$this->value] : array_values((array) $this->value);

        return implode(', ', array_map(
            static fn (array $s): string => \sprintf(
                '%s%s %s %s %s %s',
                true === ($s['inset'] ?? false) ? 'inset ' : '',
                self::member('dimension', $s['offsetX'] ?? null, '0px'),
                self::member('dimension', $s['offsetY'] ?? null, '0px'),
                self::member('dimension', $s['blur'] ?? null, '0px'),
                self::member('dimension', $s['spread'] ?? null, '0px'),
                self::member('color', $s['color'] ?? null, '#000'),
            ),
            $shadows,
        ));
    }
}
