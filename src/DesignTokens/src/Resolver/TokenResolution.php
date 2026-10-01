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

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class TokenResolution
{
    /**
     * @param array<array-key, mixed>             $tokens
     * @param array<string, ResolverSource>       $sources   winning source of each path, when traced
     * @param array<string, list<ResolverSource>> $overrides sources each path replaced, when traced
     * @param list<string>                        $documents URIs of every document the resolution read
     */
    public function __construct(
        private readonly array $tokens,
        private readonly array $sources = [],
        private readonly array $overrides = [],
        private readonly array $documents = [],
    ) {
    }

    /** @return array<array-key, mixed> */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    /** @return list<string> */
    public function getDocuments(): array
    {
        return $this->documents;
    }

    /**
     * @param list<string> $documents
     *
     * @internal
     */
    public function withDocuments(array $documents): self
    {
        return new self($this->tokens, $this->sources, $this->overrides, $documents);
    }

    public function getSource(string $path): ?ResolverSource
    {
        return $this->sources[$path] ?? null;
    }

    /** @return list<ResolverSource> */
    public function getOverrides(string $path): array
    {
        return $this->overrides[$path] ?? [];
    }
}
