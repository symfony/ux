<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Resolver;

use Symfony\UX\DesignTokens\Exception\LogicException;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface TokenResolverInterface
{
    /**
     * @param array<string, string|int|float> $inputs
     *
     * @throws LogicException when inputs are given but no Resolver document is configured
     */
    public function resolve(array $inputs): TokenResolution;

    /**
     * Every combination of inputs the Resolver document can produce; empty without one.
     *
     * @return list<array<string, string|int|float>>
     */
    public function getPermutations(): array;

    /**
     * Every modifier, with its contexts in authored order and its default; empty without a Resolver document.
     *
     * @return array<string, array{contexts: list<string>, default: string|null}>
     */
    public function getModifiers(): array;
}
