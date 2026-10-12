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

use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Exception\RuntimeException;
use Symfony\UX\DesignTokens\Exception\UnresolvedReferenceException;
use Symfony\UX\DesignTokens\Token\TokenFactory;
use Symfony\UX\DesignTokens\Token\TokenInterface;
use Symfony\UX\DesignTokens\TokenTree;
use Symfony\UX\DesignTokens\Validation\TokenValueValidator;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class TokenTreeBuilder
{
    /** Reserved token name a group uses to expose its own value (Format 6.2). */
    private const string ROOT = '$root';

    /** @var array<string, true> */
    private array $visiting = [];

    /** @var list<string> */
    private array $referenceStack = [];

    /** @var array<string, array<array-key, mixed>> */
    private array $documents = [];

    /** @var array<string, string> JSON Pointer path to source URI */
    private array $origins = [];

    /** @var array<string, true> origins of the documents merged into the tree */
    private array $merged = [];

    /** @var array<string, int> JSON Pointer path to the index of the source that last defined it */
    private array $winners = [];

    /** @var array<string, list<int>> JSON Pointer path to every source index that defined it, in order */
    private array $history = [];

    /** @var array<string, array<string, true>> group path to direct local token names */
    private array $localTokens = [];

    private bool $partial = false;

    private readonly TokenValueValidator $values;

    public function __construct(private readonly ?DocumentLoaderInterface $loader = null)
    {
        $this->values = new TokenValueValidator();
    }

    /** @return list<string> */
    public function loadedUris(): array
    {
        return array_values(array_filter(
            array_keys($this->documents),
            static fn (string $uri): bool => '' !== $uri,
        ));
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array<array-key, mixed>
     */
    public function resolve(array $raw): array
    {
        $this->reset();
        $this->documents[''] = $raw;
        $this->recordOrigins($raw, '', '', 0);

        return $this->resolveMerged($raw);
    }

    /**
     * @param list<ResolverSource> $sources
     *
     * @return array<array-key, mixed>
     */
    public function resolveSources(array $sources): array
    {
        $this->reset();
        if ([] === $sources) {
            return [];
        }

        $merged = [];
        foreach ($sources as $index => $source) {
            if (!$source instanceof ResolverSource) {
                throw new InvalidArgumentException(\sprintf('Resolver source %d must be a ResolverSource.', $index));
            }
            $merged = $this->mergeGroups($merged, $source->tokens);
            $this->recordOrigins($source->tokens, '', $this->sourceOrigin($source, $index), $index);
        }
        $this->documents[''] = $merged;

        return $this->resolveMerged($merged);
    }

    /**
     * Validates a document a Resolver completes: a token whose reference points outside it is left out.
     *
     * @return array<array-key, mixed> the tokens the document resolves on its own
     */
    public function resolvePartial(ResolverSource $source): array
    {
        $this->partial = true;
        try {
            return $this->resolveSources([$source]);
        } finally {
            $this->partial = false;
        }
    }

    /** @param list<ResolverSource> $sources */
    public function resolveWithProvenance(array $sources): TokenResolution
    {
        $tokens = $this->resolveSources($sources);

        $winningSources = [];
        $overrides = [];
        foreach (TokenTree::flatten($tokens) as $path => $_token) {
            $pointer = implode('/', array_map(JsonPointer::escape(...), explode('.', $path)));
            $winner = $this->winners[$pointer] ?? null;
            if (null === $winner || !isset($sources[$winner])) {
                continue;
            }
            $winningSources[$path] = $sources[$winner];
            foreach (array_unique($this->history[$pointer] ?? []) as $candidate) {
                if ($candidate !== $winner && isset($sources[$candidate])) {
                    $overrides[$path][] = $sources[$candidate];
                }
            }
        }

        return new TokenResolution($tokens, $winningSources, $overrides);
    }

    private function reset(): void
    {
        $this->visiting = [];
        $this->referenceStack = [];
        $this->documents = [];
        $this->origins = [];
        $this->merged = [];
        $this->winners = [];
        $this->history = [];
        $this->localTokens = [];
    }

    /**
     * @param array<array-key, mixed> $raw
     *
     * @return array<array-key, mixed>
     */
    private function resolveMerged(array $raw): array
    {
        $raw = $this->applyExtends($raw);
        $this->documents[''] = $raw;

        return $this->processNode($raw, '', null, null);
    }

    /**
     * @param array<array-key, mixed> $node
     *
     * @return array<array-key, mixed>
     */
    private function processNode(
        array $node,
        string $path,
        ?string $inheritedType,
        bool|string|null $inheritedDeprecated,
    ): array {
        $this->validateGroup($node, $path);
        $type = \is_string($node['$type'] ?? null) ? $node['$type'] : $inheritedType;
        $deprecated = $this->deprecated($node, $inheritedDeprecated);
        $result = [];

        // Format 5.2.3: a tool keeps the extension data it does not understand.
        if (\is_string($node['$description'] ?? null)) {
            $result['$description'] = $node['$description'];
        }
        if (\is_array($node['$extensions'] ?? null)) {
            $result['$extensions'] = $this->object($node['$extensions']);
        }

        $directTokens = [];
        $nestedGroups = [];
        foreach ($node as $name => $content) {
            if (str_starts_with((string) $name, '$')) {
                continue;
            }
            $this->validateName((string) $name, $this->join($path, (string) $name));
            if (!\is_array($content)) {
                throw new InvalidArgumentException(\sprintf('DTCG entry "%s" must be a token or group object.', self::display($this->join($path, (string) $name))));
            }
            $content = $this->object($content);
            if ($this->isToken($content)) {
                $directTokens[(string) $name] = $content;
            } else {
                $nestedGroups[(string) $name] = $content;
            }
        }

        $localNames = $this->localTokens[$path] ?? array_fill_keys(array_keys($directTokens), true);
        foreach ($directTokens as $name => $content) {
            if (isset($localNames[$name]) && null !== $token = $this->createToken($content, $this->join($path, (string) $name), $type, $deprecated)) {
                $result[$name] = $token;
            }
        }

        if (\array_key_exists(self::ROOT, $node)) {
            if (!\is_array($node[self::ROOT])) {
                throw new InvalidArgumentException(\sprintf('DTCG $root at "%s" must be a token object.', self::display($path)));
            }
            $root = $this->object($node[self::ROOT]);
            if (null !== $token = $this->createToken($root, $this->join($path, self::ROOT), $type, $deprecated)) {
                $result[self::ROOT] = $token;
            }
        }

        foreach ($directTokens as $name => $content) {
            if (!isset($localNames[$name]) && null !== $token = $this->createToken($content, $this->join($path, (string) $name), $type, $deprecated)) {
                $result[$name] = $token;
            }
        }

        foreach ($nestedGroups as $name => $content) {
            $result[$name] = $this->processNode($content, $this->join($path, (string) $name), $type, $deprecated);
        }

        return $result;
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return TokenInterface|null null for a partial document's token that needs another source
     */
    private function createToken(
        array $data,
        string $path,
        ?string $inheritedType,
        bool|string|null $inheritedDeprecated,
    ): ?TokenInterface {
        $this->validateToken($data, $path);
        $origin = $this->origins[$path] ?? '';

        try {
            if (\array_key_exists('$ref', $data)) {
                \assert(\is_string($data['$ref']));
                $value = $this->resolvePointer($data['$ref'], $origin);
            } else {
                $value = $this->resolveAliases($data['$value'], $origin);
            }
        } catch (UnresolvedReferenceException $e) {
            if ($this->partial) {
                return null;
            }

            throw $e;
        }

        $type = \is_string($data['$type'] ?? null) ? $data['$type'] : $this->referencedType($data, $origin);
        $type ??= $inheritedType;
        if (null === $type) {
            throw new InvalidArgumentException(\sprintf('Every DTCG token must declare, inherit, or reference a $type at "%s".', self::display($path)));
        }
        $this->values->validate($type, $value, self::display($path));

        $description = \is_string($data['$description'] ?? null) ? $data['$description'] : null;
        $extensions = \is_array($data['$extensions'] ?? null) ? $this->object($data['$extensions']) : [];
        $deprecated = $this->deprecated($data, $inheritedDeprecated);

        return TokenFactory::create($type, $value, $description, $extensions, $deprecated);
    }

    /**
     * @param array<array-key, mixed> $value
     *
     * @return array<string, mixed>
     */
    private function object(array $value): array
    {
        $object = [];
        foreach ($value as $name => $item) {
            $object[(string) $name] = $item;
        }

        return $object;
    }

    private function resolveAliases(mixed $value, string $uri): mixed
    {
        if (\is_array($value)) {
            /** @var array<mixed> $value */
            if ($this->isReferenceObject($value)) {
                \assert(\is_string($value['$ref']));

                return $this->resolvePointer($value['$ref'], $uri);
            }

            return array_map(fn (mixed $item): mixed => $this->resolveAliases($item, $uri), $value);
        }
        if (\is_string($value) && 1 === preg_match('/^\{([^{}]+)\}$/D', $value, $matches)) {
            return $this->resolveCurly($matches[1]);
        }

        return $value;
    }

    private function resolveCurly(string $path): mixed
    {
        $segments = explode('.', $path);
        foreach ($segments as $segment) {
            $this->validateReferenceSegment($segment, $path);
        }
        $node = $this->navigate($this->documents[''], $segments, '{'.$path.'}', mergedTree: true);
        if (!\is_array($node) || !$this->isToken($node)) {
            throw new RuntimeException(\sprintf('Curly brace reference must target a token: "%s".', $path));
        }
        $pointerPath = implode('/', array_map(JsonPointer::escape(...), $segments));
        $key = 'curly:'.$path;

        return $this->withCycle($key, fn (): mixed => $this->resolveTokenNodeValue($node, $this->origins[$pointerPath] ?? ''));
    }

    /** A same-document pointer addresses the merged tree (Format 7, Resolver 6.3). */
    private function resolvePointer(string $reference, string $currentUri): mixed
    {
        if (1 !== preg_match('/^(?<uri>[^#]*)#(?<fragment>(?:\/.*)?)$/D', $reference, $matches)) {
            throw new RuntimeException(\sprintf('Invalid JSON Pointer reference: "%s".', $reference));
        }
        $uri = '' === $matches['uri'] ? $this->sameDocument($currentUri) : $this->resolveUri($currentUri, $matches['uri']);
        $document = '' === $uri ? $this->documents[''] : $this->loadDocument($uri);
        $segments = JsonPointer::segments($matches['fragment']);
        $key = 'pointer:'.$uri.'#'.implode('/', array_map(JsonPointer::escape(...), $segments));

        return $this->withCycle($key, function () use ($document, $segments, $reference, $uri): mixed {
            $value = $this->navigate($document, $segments, $reference, 'pointer', '' === $uri);
            if (\is_array($value) && $this->isToken($value)) {
                return $this->resolveTokenNodeValue($value, $uri);
            }

            return $this->resolveAliases($value, $uri);
        });
    }

    /** @param array<array-key, mixed> $node */
    private function resolveTokenNodeValue(array $node, string $uri): mixed
    {
        if (\array_key_exists('$ref', $node)) {
            \assert(\is_string($node['$ref']));

            return $this->resolvePointer($node['$ref'], $uri);
        }

        return $this->resolveAliases($node['$value'], $uri);
    }

    /** @param array<array-key, mixed> $data */
    private function referencedType(array $data, string $uri): ?string
    {
        if (\array_key_exists('$ref', $data)) {
            \assert(\is_string($data['$ref']));

            return $this->typeAtPointer($data['$ref'], $uri);
        }

        return $this->typeOfValueReference($data['$value'] ?? null, $uri);
    }

    private function typeOfValueReference(mixed $value, string $uri): ?string
    {
        if (\is_string($value) && 1 === preg_match('/^\{([^{}]+)\}$/D', $value, $matches)) {
            return $this->typeAtCurly($matches[1]);
        }
        if (\is_array($value) && $this->isReferenceObject($value)) {
            \assert(\is_string($value['$ref']));

            return $this->typeAtPointer($value['$ref'], $uri);
        }

        return null;
    }

    private function typeAtCurly(string $path): ?string
    {
        $current = $this->documents[''];
        $inherited = null;
        foreach (explode('.', $path) as $segment) {
            if (\is_array($current) && \is_string($current['$type'] ?? null)) {
                $inherited = $current['$type'];
            }
            \assert(\is_array($current) && \array_key_exists($segment, $current));
            $current = $current[$segment];
        }
        \assert(\is_array($current) && $this->isToken($current));

        return $this->resolvedTokenType($current, $inherited, $this->origins[implode('/', array_map(JsonPointer::escape(...), explode('.', $path)))] ?? '');
    }

    private function typeAtPointer(string $reference, string $currentUri): ?string
    {
        $matched = preg_match('/^(?<uri>[^#]*)#(?<fragment>(?:\/.*)?)$/D', $reference, $matches);
        \assert(1 === $matched);
        $uri = '' === $matches['uri'] ? $this->sameDocument($currentUri) : $this->resolveUri($currentUri, $matches['uri']);
        $document = '' === $uri ? $this->documents[''] : $this->loadDocument($uri);
        $current = $document;
        $inherited = null;
        $segments = JsonPointer::segments($matches['fragment']);
        foreach ($segments as $index => $segment) {
            // "#/token/$value" is the token; deeper segments point inside its value.
            if (\is_array($current) && $this->isToken($current)) {
                return '$value' === $segment && $index === array_key_last($segments)
                    ? $this->resolvedTokenType($current, $inherited, $uri)
                    : null;
            }
            if (\is_array($current) && \is_string($current['$type'] ?? null)) {
                $inherited = $current['$type'];
            }
            \assert(\is_array($current) && \array_key_exists($segment, $current));
            $current = $current[$segment];
        }

        if (!\is_array($current) || !$this->isToken($current)) {
            return $inherited;
        }

        return $this->resolvedTokenType($current, $inherited, $uri);
    }

    /** @param array<array-key, mixed> $token */
    private function resolvedTokenType(array $token, ?string $inheritedType, string $uri): ?string
    {
        if (\is_string($token['$type'] ?? null)) {
            return $token['$type'];
        }
        if (\array_key_exists('$ref', $token)) {
            \assert(\is_string($token['$ref']));

            return $this->typeAtPointer($token['$ref'], $uri);
        }

        return $this->typeOfValueReference($token['$value'] ?? null, $uri) ?? $inheritedType;
    }

    /**
     * @param array<array-key, mixed> $tree
     *
     * @return array<array-key, mixed>
     */
    private function applyExtends(array $tree): array
    {
        return $this->inheritNode($tree, $tree, '', []);
    }

    /**
     * @param array<array-key, mixed> $node
     * @param array<array-key, mixed> $root
     * @param list<string>            $stack
     *
     * @return array<array-key, mixed>
     */
    private function inheritNode(array $node, array $root, string $path, array $stack): array
    {
        $referenceProperty = \array_key_exists('$extends', $node) ? '$extends' : null;
        if (null === $referenceProperty && $this->isGroupReference($node, $root)) {
            $referenceProperty = '$ref';
        }

        if (null !== $referenceProperty) {
            $localTokenNames = [];
            foreach ($node as $name => $value) {
                if (!str_starts_with((string) $name, '$') && \is_array($value)
                    && $this->isToken($value) && !$this->isGroupReference($value, $root)) {
                    $localTokenNames[(string) $name] = true;
                }
            }
            $this->localTokens[$path] = $localTokenNames;
            $extends = $node[$referenceProperty];
            if (!\is_string($extends)) {
                throw new InvalidArgumentException(\sprintf('%s must be a reference string at "%s".', $referenceProperty, self::display($path)));
            }
            if (\in_array($path, $stack, true)) {
                throw new RuntimeException(\sprintf('Circular group extension detected: "%s".', implode(' -> ', array_map(self::display(...), [...$stack, $path]))));
            }
            $stack[] = $path;
            $segments = $this->extendsSegments($extends);
            $basePath = implode('/', array_map(JsonPointer::escape(...), $segments));
            try {
                $base = $this->navigate($root, $segments, $extends, 'extends', true);
            } catch (UnresolvedReferenceException $e) {
                if (!$this->partial) {
                    throw $e;
                }
                $base = null;
            }
            unset($node[$referenceProperty]);
            if (null !== $base) {
                if (!\is_array($base) || !$this->rawNodeIsGroup($base, $root, [])) {
                    throw new InvalidArgumentException(\sprintf('$extends target must be a group: "%s".', $extends));
                }
                $base = $this->inheritNode($base, $root, $basePath, $stack);
                $node = $this->mergeGroups($base, $node);
                $this->copyOrigins($basePath, $path, $base);
            }
        }

        foreach ($node as $name => $value) {
            if (\is_array($value) && !str_starts_with((string) $name, '$')
                && (!$this->isToken($value) || $this->isGroupReference($value, $root))) {
                $node[$name] = $this->inheritNode($value, $root, $this->join($path, (string) $name), $stack);
            }
        }

        return $node;
    }

    /**
     * @param array<mixed>            $node
     * @param array<array-key, mixed> $root
     */
    private function isGroupReference(array $node, array $root): bool
    {
        if (!\array_key_exists('$ref', $node) || \array_key_exists('$value', $node) || !\is_string($node['$ref'])) {
            return false;
        }

        foreach ($node as $name => $_value) {
            if (!str_starts_with((string) $name, '$')) {
                return true;
            }
        }

        if (!str_starts_with($node['$ref'], '#')) {
            return false;
        }

        $segments = JsonPointer::segments(substr($node['$ref'], 1));

        // A pointer through `$value` lands inside a token, never on a group.
        if (\in_array('$value', $segments, true)) {
            return false;
        }

        try {
            $target = $this->navigate($root, $segments, $node['$ref'], 'pointer');
        } catch (\RuntimeException) {
            return false;
        }

        return \is_array($target) && $this->rawNodeIsGroup($target, $root, []);
    }

    /**
     * @param array<mixed>            $node
     * @param array<array-key, mixed> $root
     * @param array<string, true>     $seen
     */
    private function rawNodeIsGroup(array $node, array $root, array $seen): bool
    {
        if (\array_key_exists('$value', $node)) {
            return false;
        }
        if (\array_key_exists('$extends', $node)) {
            return true;
        }
        foreach ($node as $name => $_value) {
            if (!str_starts_with((string) $name, '$')) {
                return true;
            }
        }
        if (!\array_key_exists('$ref', $node)) {
            return true;
        }
        if (!\is_string($node['$ref']) || !str_starts_with($node['$ref'], '#') || isset($seen[$node['$ref']])) {
            return false;
        }
        $segments = JsonPointer::segments(substr($node['$ref'], 1));
        if (\in_array('$value', $segments, true)) {
            return false;
        }
        $seen[$node['$ref']] = true;

        try {
            $target = $this->navigate($root, $segments, $node['$ref'], 'pointer');
        } catch (\RuntimeException) {
            return false;
        }

        return \is_array($target) && $this->rawNodeIsGroup($target, $root, $seen);
    }

    /** @return list<string> */
    private function extendsSegments(string $extends): array
    {
        if (1 === preg_match('/^\{([^{}]+)\}$/D', $extends, $matches)) {
            return explode('.', $matches[1]);
        }
        if (str_starts_with($extends, '#')) {
            return JsonPointer::segments(substr($extends, 1));
        }
        throw new InvalidArgumentException(\sprintf('$extends must be a curly brace reference or a JSON Pointer, got "%s".', $extends));
    }

    /**
     * @param array<mixed> $base
     * @param array<mixed> $override
     *
     * @return array<mixed>
     */
    private function mergeGroups(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (\is_array($value) && \is_array($base[$key] ?? null)
                && !$this->isToken($value) && !$this->isToken($base[$key])) {
                $base[$key] = $this->mergeGroups($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /** @param array<array-key, mixed> $node */
    private function validateGroup(array $node, string $path): void
    {
        $allowed = ['$type', '$description', '$extensions', '$extends', '$deprecated', '$root'];
        foreach ($node as $key => $value) {
            if (str_starts_with((string) $key, '$') && !\in_array($key, $allowed, true)) {
                throw new InvalidArgumentException(\sprintf('Unknown DTCG group property "%s" at "%s".', $key, self::display($path)));
            }
        }
        $this->validateMetadata($node, $path);
    }

    /** @param array<array-key, mixed> $node */
    private function validateToken(array $node, string $path): void
    {
        $allowed = ['$value', '$type', '$ref', '$description', '$extensions', '$deprecated'];
        foreach ($node as $key => $value) {
            if (\in_array($key, $allowed, true)) {
                continue;
            }
            if (!str_starts_with((string) $key, '$')) {
                throw new InvalidArgumentException(\sprintf('DTCG token "%s" cannot hold the child "%s": a node defining $value or $ref is a token, not a group.', self::display($path), $key));
            }

            throw new InvalidArgumentException(\sprintf('Unknown DTCG token property "%s" at "%s".', $key, self::display($path)));
        }
        if (\array_key_exists('$value', $node) === \array_key_exists('$ref', $node)) {
            throw new InvalidArgumentException(\sprintf('DTCG token "%s" must define exactly one of $value or $ref.', self::display($path)));
        }
        if (\array_key_exists('$ref', $node) && !\is_string($node['$ref'])) {
            throw new InvalidArgumentException(\sprintf('DTCG token $ref at "%s" must be a JSON Pointer string.', self::display($path)));
        }
        $this->validateMetadata($node, $path);
    }

    /** @param array<array-key, mixed> $node */
    private function validateMetadata(array $node, string $path): void
    {
        if (\array_key_exists('$type', $node)
            && (!\is_string($node['$type']) || !\in_array($node['$type'], TokenValueValidator::TYPES, true))) {
            throw new InvalidArgumentException(\sprintf('%s at "%s" is not a DTCG type. Expected one of: %s.', \is_string($node['$type']) ? '"'.$node['$type'].'"' : 'A non-string $type', self::display($path), implode(', ', TokenValueValidator::TYPES)));
        }
        if (\array_key_exists('$description', $node) && !\is_string($node['$description'])) {
            throw new InvalidArgumentException(\sprintf('DTCG $description at "%s" must be a string.', self::display($path)));
        }
        if (\array_key_exists('$extensions', $node) && (!\is_array($node['$extensions']) || array_is_list($node['$extensions']))) {
            throw new InvalidArgumentException(\sprintf('DTCG $extensions at "%s" must be an object.', self::display($path)));
        }
        if (\array_key_exists('$deprecated', $node) && !\is_bool($node['$deprecated']) && !\is_string($node['$deprecated'])) {
            throw new InvalidArgumentException(\sprintf('DTCG $deprecated at "%s" must be a boolean or string.', self::display($path)));
        }
    }

    /** @param array<array-key, mixed> $node */
    private function deprecated(array $node, bool|string|null $inherited): bool|string|null
    {
        if (!\array_key_exists('$deprecated', $node)) {
            return $inherited;
        }
        $value = $node['$deprecated'];
        \assert(\is_bool($value) || \is_string($value));

        return $value;
    }

    private function validateName(string $name, string $path): void
    {
        if ('' === $name || str_starts_with($name, '$') || str_contains($name, '.') || str_contains($name, '{') || str_contains($name, '}')) {
            throw new InvalidArgumentException(\sprintf('Invalid DTCG token or group name "%s" at "%s".', $name, self::display($path)));
        }
    }

    /** Format 6.7.2: `$root` is the one `$` name a reference may address. */
    private function validateReferenceSegment(string $segment, string $path): void
    {
        if (self::ROOT === $segment) {
            return;
        }

        $this->validateName($segment, $path);
    }

    /** @param array<mixed> $node */
    private function isToken(array $node): bool
    {
        return \array_key_exists('$value', $node) || \array_key_exists('$ref', $node);
    }

    /**
     * @param array<mixed>                $document
     * @param list<string>                $segments
     * @param 'curly'|'extends'|'pointer' $mode       the syntax being walked, which decides what a
     *                                                failed step means and how it is reported
     * @param bool                        $mergedTree whether $document is the merged tree, where a
     *                                                missing entry may come from another source
     */
    private function navigate(array $document, array $segments, string $reference, string $mode = 'curly', bool $mergedTree = false): mixed
    {
        $current = $document;
        $walked = [];
        foreach ($segments as $segment) {
            // Only a JSON Pointer can address a location inside a value.
            if ('pointer' !== $mode && [] !== $walked && \is_array($current) && $this->isToken($current)) {
                $hint = 'curly' === $mode ? '; use a $ref JSON Pointer' : '';

                throw new RuntimeException(\sprintf('Reference "%s" cannot address "%s" inside the value of token "%s"%s.', $reference, $segment, implode('.', $walked), $hint));
            }
            if (!\is_array($current) || !\array_key_exists($segment, $current)) {
                $message = \sprintf('Reference target not found: "%s".', $reference);

                throw $mergedTree && \is_array($current) ? new UnresolvedReferenceException($message) : new RuntimeException($message);
            }
            if ('pointer' !== $mode && array_is_list($current)) {
                $syntax = 'curly' === $mode ? 'Curly brace references' : '$extends';

                throw new RuntimeException(\sprintf('%s cannot access array elements: "%s".', $syntax, $reference));
            }
            $walked[] = $segment;
            $current = $current[$segment];
        }

        return $current;
    }

    /** The merged tree for a merged source, the file itself for a referenced one. */
    private function sameDocument(string $currentUri): string
    {
        return isset($this->merged[$currentUri]) ? '' : $currentUri;
    }

    private function resolveUri(string $baseUri, string $reference): string
    {
        if (1 === preg_match('#^[a-z][a-z0-9+.-]*:#i', $reference) || str_starts_with($reference, '/')) {
            return $reference;
        }
        $authority = '';
        if (1 === preg_match('#^([a-z][a-z0-9+.-]*://[^/]*)(/.*)?$#i', $baseUri, $matches)) {
            $authority = $matches[1];
            $baseUri = $matches[2] ?? '/';
        }
        $directory = '' === $baseUri ? '' : \dirname($baseUri);
        $path = ('.' === $directory ? '' : $directory.'/').$reference;
        $absolute = str_starts_with($path, '/') || '' !== $authority;
        $parts = [];
        foreach (explode('/', $path) as $part) {
            if ('' === $part || '.' === $part) {
                continue;
            }
            if ('..' === $part && [] !== $parts && '..' !== $parts[array_key_last($parts)]) {
                array_pop($parts);
            } else {
                $parts[] = $part;
            }
        }

        return $authority.($absolute ? '/' : '').implode('/', $parts);
    }

    /** @return array<array-key, mixed> */
    private function loadDocument(string $uri): array
    {
        if (isset($this->documents[$uri])) {
            return $this->documents[$uri];
        }
        if (null === $this->loader) {
            throw new RuntimeException(\sprintf('Cannot load referenced token document "%s" without a loader.', $uri));
        }
        $document = $this->loader->load($uri);
        $this->documents[$uri] = $document;

        return $document;
    }

    /**
     * @template T
     *
     * @param callable(): T $resolve
     *
     * @return T
     */
    private function withCycle(string $key, callable $resolve): mixed
    {
        if (isset($this->visiting[$key])) {
            $offset = array_search($key, $this->referenceStack, true);
            $chain = false === $offset ? [$key, $key] : [...\array_slice($this->referenceStack, $offset), $key];

            throw new RuntimeException(\sprintf('Circular reference detected: "%s".', implode(' -> ', $chain)));
        }
        $this->visiting[$key] = true;
        $this->referenceStack[] = $key;
        try {
            return $resolve();
        } finally {
            unset($this->visiting[$key]);
            array_pop($this->referenceStack);
        }
    }

    /** @param array<mixed> $node */
    private function recordOrigins(array $node, string $path, string $uri, int $source): void
    {
        $this->merged[$uri] = true;
        $this->origins[$path] = $uri;
        $this->winners[$path] = $source;
        $this->history[$path][] = $source;
        foreach ($node as $name => $value) {
            if (\is_array($value)) {
                $this->recordOrigins($value, $this->join($path, (string) $name), $uri, $source);
            }
        }
    }

    /** @param array<mixed> $node */
    private function copyOrigins(string $from, string $to, array $node): void
    {
        foreach ($node as $name => $value) {
            $source = $this->join($from, (string) $name);
            $target = $this->join($to, (string) $name);
            if (!isset($this->origins[$target]) && isset($this->origins[$source])) {
                $this->origins[$target] = $this->origins[$source];
            }
            if (!isset($this->winners[$target]) && isset($this->winners[$source])) {
                $this->winners[$target] = $this->winners[$source];
            }
            if (!isset($this->history[$target]) && isset($this->history[$source])) {
                $this->history[$target] = $this->history[$source];
            }
            if (\is_array($value)) {
                $this->copyOrigins($source, $target, $value);
            }
        }
    }

    private static function display(string $path): string
    {
        return str_replace(['~1', '~0'], ['/', '~'], str_replace('/', '.', $path));
    }

    private function join(string $path, string $segment): string
    {
        $escaped = JsonPointer::escape($segment);

        return '' === $path ? $escaped : $path.'/'.$escaped;
    }

    private function sourceOrigin(ResolverSource $source, int $index): string
    {
        $filename = null === $source->uri ? '.inline-'.$index.'.tokens.json' : basename(parse_url($source->uri, \PHP_URL_PATH) ?: $source->uri);

        return '' === $source->basePath ? $filename : rtrim($source->basePath, '/').'/'.$filename;
    }

    /** @param array<mixed> $value */
    private function isReferenceObject(array $value): bool
    {
        return ['$ref'] === array_keys($value) && \is_string($value['$ref']);
    }
}
