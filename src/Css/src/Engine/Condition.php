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
 * Port of Panda's `ConditionDetails`: an at-rule, a selector nesting, or a list of them.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Condition
{
    public const AT_RULE = 'at-rule';
    public const SELF_NESTING = 'self-nesting';
    public const PARENT_NESTING = 'parent-nesting';
    public const COMBINATOR_NESTING = 'combinator-nesting';
    public const MIXED = 'mixed';
    public const MULTI_BLOCK = 'multi-block';

    /**
     * @param string|list<self>  $value the query, or the parts of a mixed or multi-block condition
     * @param string|list<mixed> $raw
     */
    public function __construct(
        public readonly string $type,
        public readonly string|array $value,
        public readonly string|array $raw,
        public readonly ?string $name = null,
        public readonly ?string $params = null,
    ) {
    }

    public function isAtRule(): bool
    {
        return self::AT_RULE === $this->type;
    }

    public function isNesting(): bool
    {
        return str_contains($this->type, 'nesting');
    }

    public function isPseudoElement(): bool
    {
        return \is_string($this->raw) && 1 === preg_match('/::[\w-]/', $this->raw);
    }
}
