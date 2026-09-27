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
 * Port of Panda's `Token` (packages/token-dictionary/src/token.ts).
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Token
{
    public mixed $originalValue;

    /**
     * @param list<string>         $path
     * @param array<string, mixed> $extensions
     */
    public function __construct(
        public string $name,
        public mixed $value,
        public array $path,
        public array $extensions = [],
    ) {
        $this->originalValue = $value;
        $this->extensions['condition'] ??= 'base';
    }

    public function copy(): self
    {
        $copy = clone $this;
        $copy->originalValue = $this->value;

        return $copy;
    }

    public function isConditional(): bool
    {
        return JsValue::isTruthy($this->extensions['conditions'] ?? null);
    }

    public function isComposite(): bool
    {
        return Tokens::isComposite($this->originalValue);
    }
}
