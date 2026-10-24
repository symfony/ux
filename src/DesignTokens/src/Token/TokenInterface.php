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
 * A resolved design token: getValue() holds the DTCG value, (string) its CSS representation.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
interface TokenInterface extends \Stringable
{
    public function getType(): string;

    /** The DTCG value, with every alias and reference resolved. */
    public function getValue(): mixed;

    public function getDescription(): ?string;

    /** Whether the token or its closest group declares `$deprecated`. */
    public function isDeprecated(): bool;

    public function getDeprecationMessage(): ?string;

    /**
     * The token's own `$extensions`, not inherited from its groups.
     *
     * @return array<string, mixed>
     */
    public function getExtensions(): array;
}
