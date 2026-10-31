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

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\UX\DesignTokens\Exception\LogicException;
use Symfony\UX\DesignTokens\Exception\UnexpectedValueException;
use Symfony\UX\DesignTokens\TokenTree;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class ConfiguredTokenResolver implements TokenResolverInterface
{
    /** Bump when the cached payload changes. */
    private const int CACHE_FORMAT = 3;

    /**
     * @param list<string> $paths token files merged in order, after the Resolver selection
     */
    public function __construct(
        private readonly DocumentLoaderInterface $loader = new JsonDocumentLoader(),
        private readonly array $paths = [],
        private readonly ?string $resolverPath = null,
        private readonly ?CacheInterface $cache = null,
        private readonly bool $debug = false,
    ) {
    }

    public function resolve(array $inputs): TokenResolution
    {
        if (null === $this->cache) {
            return $this->build($inputs);
        }

        ksort($inputs);
        // A configuration change must not reuse an older entry.
        $key = 'ux_design_tokens.'.self::CACHE_FORMAT.'.'.hash('xxh128', serialize([$this->paths, $this->resolverPath])).'.'.ResolverInputs::key($inputs);
        $compute = function () use ($inputs): array {
            $resolution = $this->build($inputs);

            return [
                'format' => self::CACHE_FORMAT,
                'documents' => $resolution->getDocuments(),
                'signature' => $this->signature($resolution->getDocuments()),
                'tokens' => TokenTree::export($resolution->getTokens()),
            ];
        };

        [$documents, $signature, $tokens] = $this->entry($this->cache->get($key, $compute));
        if ($this->debug && $signature !== $this->signature($documents)) {
            [$documents, , $tokens] = $this->entry($this->cache->get($key, $compute, \INF));
        }

        return new TokenResolution(TokenTree::hydrate($tokens), documents: $documents);
    }

    /**
     * Resolve again, recording which source won each path and which ones it replaced.
     *
     * @param array<string, string|int|float> $inputs
     */
    public function trace(array $inputs): TokenResolution
    {
        $document = $this->document();
        $builder = new TokenTreeBuilder($this->loader);

        return $builder->resolveWithProvenance($this->sourcesFor($document, $inputs))->withDocuments($this->documents($builder, $document));
    }

    public function getPermutations(): array
    {
        return $this->document()?->getPermutations() ?? [];
    }

    public function getModifiers(): array
    {
        return $this->document()?->getModifiers() ?? [];
    }

    /**
     * @param array<string, string|int|float> $inputs
     *
     * @return list<ResolverSource>
     */
    private function sourcesFor(?ResolverDocument $document, array $inputs): array
    {
        if (null === $document && [] !== $inputs) {
            throw new LogicException('Resolver inputs cannot be selected because no Resolver document is configured ("ux_design_tokens.resolver.path").');
        }

        $sources = array_map(
            fn (string $path): ResolverSource => new ResolverSource($this->loader->load($path), '.' === \dirname($path) ? '' : \dirname($path), $path),
            $this->paths,
        );

        return [...($document?->sourceDescriptors($inputs) ?? []), ...$sources];
    }

    private function document(): ?ResolverDocument
    {
        if (null === $this->resolverPath) {
            return null;
        }

        $directory = \dirname($this->resolverPath);

        return new ResolverDocument($this->loader->load($this->resolverPath), '.' === $directory ? '' : $directory, $this->loader);
    }

    /** @return list<string> */
    private function documents(TokenTreeBuilder $builder, ?ResolverDocument $document): array
    {
        $uris = [...$this->paths, $this->resolverPath, ...$builder->loadedUris(), ...($document?->loadedUris() ?? [])];

        return array_values(array_unique(array_filter($uris, static fn (?string $uri): bool => null !== $uri && '' !== $uri)));
    }

    /** @param array<string, string|int|float> $inputs */
    private function build(array $inputs): TokenResolution
    {
        $document = $this->document();
        $builder = new TokenTreeBuilder($this->loader);
        $tokens = $builder->resolveSources($this->sourcesFor($document, $inputs));

        return new TokenResolution($tokens, documents: $this->documents($builder, $document));
    }

    /**
     * @return array{list<string>, string, array<array-key, mixed>}
     */
    private function entry(mixed $data): array
    {
        if (!\is_array($data)
            || self::CACHE_FORMAT !== ($data['format'] ?? null)
            || !\is_string($signature = $data['signature'] ?? null)
            || !\is_array($rawDocuments = $data['documents'] ?? null)
            || !\is_array($tokens = $data['tokens'] ?? null)
        ) {
            throw new UnexpectedValueException('The Design Tokens cache holds an entry this version cannot read. Clear the cache to rebuild it.');
        }

        $documents = [];
        foreach ($rawDocuments as $document) {
            if (!\is_string($document)) {
                throw new UnexpectedValueException('The Design Tokens cache must record documents as strings.');
            }
            $documents[] = $document;
        }

        return [$documents, $signature, $tokens];
    }

    /** @param list<string> $documents */
    private function signature(array $documents): string
    {
        if (!$this->debug) {
            return '';
        }

        $stats = [];
        foreach ($documents as $document) {
            if (is_file($document)) {
                $stats[] = [$document, @filemtime($document), @filesize($document)];
            }
        }

        return hash('xxh128', serialize($stats));
    }
}
