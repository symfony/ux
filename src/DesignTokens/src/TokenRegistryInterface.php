<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens;

use Symfony\UX\DesignTokens\Exception\LogicException;
use Symfony\UX\DesignTokens\Exception\ResolverException;
use Symfony\UX\DesignTokens\Exception\TokenNotFoundException;
use Symfony\UX\DesignTokens\Token\TokenInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
interface TokenRegistryInterface
{
    /**
     * Inputs select Resolver contexts for this call only; names are case-insensitive.
     *
     * @param string                          $path   e.g. `'color.brand.primary'`
     * @param array<string, string|int|float> $inputs
     *
     * @throws TokenNotFoundException when the path does not resolve to a token
     * @throws ResolverException      when an input names an unknown modifier or context
     * @throws LogicException         when inputs apply and no Resolver document is configured
     */
    public function get(string $path, array $inputs = []): TokenInterface;

    /**
     * Returns the token, or null when the path is missing or names a group.
     *
     * @param array<string, string|int|float> $inputs
     *
     * @throws ResolverException when an input names an unknown modifier or context
     * @throws LogicException    when inputs apply and no Resolver document is configured
     */
    public function find(string $path, array $inputs = []): ?TokenInterface;

    /**
     * Whether the path names a token, not a group.
     *
     * @param array<string, string|int|float> $inputs
     *
     * @throws ResolverException when an input names an unknown modifier or context
     * @throws LogicException    when inputs apply and no Resolver document is configured
     */
    public function has(string $path, array $inputs = []): bool;

    /**
     * @param array<string, string|int|float> $inputs
     *
     * @return array<array-key, mixed>
     *
     * @throws ResolverException when an input names an unknown modifier or context
     * @throws LogicException    when inputs apply and no Resolver document is configured
     */
    public function all(array $inputs = []): array;

    /**
     * @param array<string, string|int|float> $inputs
     *
     * @return array<string, TokenInterface>
     *
     * @throws ResolverException when an input names an unknown modifier or context
     * @throws LogicException    when inputs apply and no Resolver document is configured
     */
    public function flatten(array $inputs = []): array;

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
