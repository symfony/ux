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

use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\DesignTokens\Exception\TokenNotFoundException;
use Symfony\UX\DesignTokens\Resolver\ConfiguredTokenResolver;
use Symfony\UX\DesignTokens\Resolver\ResolverInputs;
use Symfony\UX\DesignTokens\Resolver\TokenResolverInterface;
use Symfony\UX\DesignTokens\Token\TokenInterface;

/**
 * Keeps each resolution until reset() is called.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class TokenRegistry implements TokenRegistryInterface, ResetInterface
{
    /** @var array<string, array<array-key, mixed>> resolved trees keyed by their inputs */
    private array $trees = [];

    /**
     * @param array<string, string|int|float> $defaultInputs Resolver inputs used when a call selects none
     */
    public function __construct(
        private readonly TokenResolverInterface $resolver = new ConfiguredTokenResolver(),
        private readonly array $defaultInputs = [],
    ) {
    }

    public function get(string $path, array $inputs = []): TokenInterface
    {
        return $this->tokenAt($this->all($inputs), $path);
    }

    public function find(string $path, array $inputs = []): ?TokenInterface
    {
        try {
            return $this->tokenAt($this->all($inputs), $path);
        } catch (TokenNotFoundException) {
            return null;
        }
    }

    public function has(string $path, array $inputs = []): bool
    {
        return null !== $this->find($path, $inputs);
    }

    public function all(array $inputs = []): array
    {
        $inputs = ResolverInputs::merge($this->defaultInputs, $inputs);
        ksort($inputs);

        return $this->trees[ResolverInputs::key($inputs)] ??= $this->resolver->resolve($inputs)->getTokens();
    }

    public function flatten(array $inputs = []): array
    {
        return TokenTree::flatten($this->all($inputs));
    }

    public function getPermutations(): array
    {
        return $this->resolver->getPermutations();
    }

    public function getModifiers(): array
    {
        return $this->resolver->getModifiers();
    }

    public function reset(): void
    {
        $this->trees = [];
    }

    /**
     * @param array<array-key, mixed> $tokens
     */
    private function tokenAt(array $tokens, string $path): TokenInterface
    {
        $current = $tokens;
        foreach (explode('.', $path) as $part) {
            if (str_starts_with($part, '$') && '$root' !== $part) {
                throw new TokenNotFoundException($path, \sprintf('"%s" names the DTCG property "%s", not a token.', $path, $part));
            }
            if (!\is_array($current) || !\array_key_exists($part, $current)) {
                throw new TokenNotFoundException($path);
            }
            $current = $current[$part];
        }

        if (!$current instanceof TokenInterface) {
            throw new TokenNotFoundException($path, \sprintf('"%s" is a token group, not a token.', $path));
        }

        return $current;
    }
}
