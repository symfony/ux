<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Engine;

/**
 * What a utility transform receives besides the value, like Panda's `TransformArgs`.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class TransformArgs
{
    /**
     * @param string|int|float|bool $raw the value before it was resolved against the utility values
     */
    public function __construct(
        public readonly string|int|float|bool $raw,
        private readonly Tokens $tokens,
    ) {
    }

    public function token(string $path): ?string
    {
        return $this->tokens->getVar($path);
    }

    public function hasToken(string $path): bool
    {
        return $this->tokens->has($path);
    }

    /**
     * @return array{invalid: bool, color?: string, value: string|int|float|bool}
     */
    public function colorMix(string|int|float|bool $value): array
    {
        if (!\is_string($value) || '' === $value) {
            return ['invalid' => true, 'value' => $value];
        }

        return $this->tokens->colorMix($value);
    }
}
