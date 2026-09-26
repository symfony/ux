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

use Symfony\Component\Filesystem\Path;
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Exception\ResolverException;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class ResolverDocument
{
    public const VERSION = '2025.10';

    /** @var array<string, array<array-key, mixed>> */
    private array $documents = [];

    /** @var list<array{type: 'set'|'modifier', name: string, item: array<array-key, mixed>}>|null */
    private ?array $order = null;

    /** @var array<string, mixed> */
    private readonly array $data;

    /**
     * @param array<array-key, mixed>      $data     the decoded document
     * @param string                       $basePath directory its relative sources are read from
     * @param DocumentLoaderInterface|null $loader   reads those sources; the same loader must read the document itself
     */
    public function __construct(
        array $data,
        private readonly string $basePath = '',
        private readonly ?DocumentLoaderInterface $loader = null,
    ) {
        $document = [];
        foreach ($data as $key => $value) {
            if (!\is_string($key)) {
                throw new InvalidArgumentException('A Resolver document must use string property names.');
            }
            $document[$key] = $value;
        }
        $this->data = $document;
        $this->validateDocument();
    }

    /**
     * @return list<string> URIs of the external documents read so far
     *
     * @internal
     */
    public function loadedUris(): array
    {
        return array_keys($this->documents);
    }

    /**
     * @param array<string, mixed> $inputs
     *
     * @return list<ResolverSource>
     *
     * @internal
     */
    public function sourceDescriptors(array $inputs = []): array
    {
        $sources = [];
        foreach ($this->resolutionPlan($inputs) as $selection) {
            foreach ($selection['sources'] as $source) {
                $sources[] = $source;
            }
        }

        return $sources;
    }

    /** @return array<string, array{contexts: list<string>, default: string|null}> */
    public function getModifiers(): array
    {
        $modifiers = [];
        foreach ($this->modifiersIn($this->resolutionOrder()) as $name => $modifier) {
            $modifiers[$name] = [
                'contexts' => array_map(strval(...), array_keys($this->contexts($modifier, $name))),
                'default' => \array_key_exists('default', $modifier) ? $this->defaultContext($modifier, $name) : null,
            ];
        }

        return $modifiers;
    }

    /**
     * @param array<string, mixed> $inputs
     *
     * @return list<array{type: 'set'|'modifier', name: string, context: ?string, sources: list<ResolverSource>}>
     *
     * @internal
     */
    public function resolutionPlan(array $inputs = []): array
    {
        $order = $this->resolutionOrder();
        $modifiers = $this->modifiersIn($order);
        $inputs = $this->validateInputs($inputs, $modifiers);
        $plan = [];

        foreach ($order as $position => $entry) {
            $item = $entry['item'];
            $context = null;

            if ('modifier' === $entry['type']) {
                $name = $entry['name'];
                $contexts = $this->contexts($item, $name);
                $context = $inputs[$name] ?? $this->defaultContext($item, $name);
                $sources = $this->expandSources($contexts[$context], 'modifier', ["#/resolutionOrder/{$position}"], $this->basePath);
            } else {
                /** @var list<mixed> $rawSources */
                $rawSources = $item['sources'];
                $sources = $this->expandSources($rawSources, 'set', ["#/resolutionOrder/{$position}"], $this->basePath);
            }

            $plan[] = [
                'type' => $entry['type'],
                'name' => $entry['name'],
                'context' => $context,
                'sources' => $sources,
            ];
        }

        return $plan;
    }

    /** @return list<array<string, string>> */
    public function getPermutations(): array
    {
        $modifiers = $this->modifiersIn($this->resolutionOrder());
        $permutations = [[]];

        foreach ($modifiers as $name => $modifier) {
            $next = [];
            foreach ($permutations as $permutation) {
                foreach (array_keys($this->contexts($modifier, $name)) as $context) {
                    $next[] = array_replace($permutation, [$name => $context]);
                }
            }
            $permutations = $next;
        }

        return $permutations;
    }

    /** @return list<array{type: 'set'|'modifier', name: string, item: array<array-key, mixed>}> */
    private function resolutionOrder(): array
    {
        return $this->order ??= $this->computeResolutionOrder();
    }

    /**
     * @phpstan-impure Resolving the order validates references and may load files.
     *
     * @return list<array{type: 'set'|'modifier', name: string, item: array<array-key, mixed>}>
     */
    private function computeResolutionOrder(): array
    {
        /** @var list<mixed> $rawOrder */
        $rawOrder = $this->data['resolutionOrder'];
        $order = [];
        $orderNames = [];

        foreach ($rawOrder as $position => $rawItem) {
            if (!\is_array($rawItem) || array_is_list($rawItem)) {
                throw new InvalidArgumentException(\sprintf('resolutionOrder[%d] must be an object.', $position));
            }
            /** @var array<string, mixed> $rawItem */
            if (\array_key_exists('$ref', $rawItem)) {
                $ref = $this->referenceString($rawItem, "resolutionOrder[{$position}]");
                $target = $this->resolveReference($rawItem, 'resolutionOrder', ["#/resolutionOrder/{$position}"], $this->basePath, $this->data);
                $type = $this->referencedItemType($ref, $target);
                $name = $this->referencedItemName($ref, $target);
                $this->validateItem($target, $type, $name, !preg_match('~^#/(?:sets|modifiers)/[^/]+$~', $ref));
                if (isset($orderNames[$name])) {
                    throw new InvalidArgumentException(\sprintf('Resolution order name "%s" is duplicated.', $name));
                }
                $orderNames[$name] = true;
                $order[] = ['type' => $type, 'name' => $name, 'item' => $target];

                continue;
            }

            $type = $rawItem['type'] ?? null;
            $name = $rawItem['name'] ?? null;
            if (!\is_string($type)) {
                throw new InvalidArgumentException(\sprintf('Inline resolutionOrder item %d must declare type "set" or "modifier".', $position));
            }
            $itemType = $this->itemType($type, $position);
            if (!\is_string($name) || '' === $name) {
                throw new InvalidArgumentException(\sprintf('Inline resolutionOrder item %d must declare a non-empty name.', $position));
            }
            if (isset($orderNames[$name])) {
                throw new InvalidArgumentException(\sprintf('Resolution order name "%s" is duplicated.', $name));
            }
            $orderNames[$name] = true;
            $this->validateItem($rawItem, $itemType, $name, true);
            $order[] = ['type' => $itemType, 'name' => $name, 'item' => $rawItem];
        }

        return $order;
    }

    /** @return 'set'|'modifier' */
    private function itemType(string $type, int $position): string
    {
        return match ($type) {
            'set' => 'set',
            'modifier' => 'modifier',
            default => throw new InvalidArgumentException(\sprintf('Inline resolutionOrder item %d must declare type "set" or "modifier".', $position)),
        };
    }

    /**
     * @param list<array{type: 'set'|'modifier', name: string, item: array<array-key, mixed>}> $order
     *
     * @return array<string, array<array-key, mixed>>
     */
    private function modifiersIn(array $order): array
    {
        $modifiers = [];
        foreach ($order as $entry) {
            if ('modifier' !== $entry['type']) {
                continue;
            }
            $modifiers[$entry['name']] = $entry['item'];
        }

        return $modifiers;
    }

    /**
     * @param array<string, mixed>                   $inputs
     * @param array<string, array<array-key, mixed>> $modifiers
     *
     * @return array<string, string>
     */
    private function validateInputs(array $inputs, array $modifiers): array
    {
        $normalizedModifiers = [];
        foreach ($modifiers as $name => $modifier) {
            $key = strtolower($name);
            if (isset($normalizedModifiers[$key])) {
                throw new InvalidArgumentException(\sprintf('Modifier names "%s" and "%s" differ only by case.', $normalizedModifiers[$key]['name'], $name));
            }
            $normalizedModifiers[$key] = ['name' => $name, 'item' => $modifier];
        }

        $selected = [];
        $provided = [];
        $seenInputs = [];
        $errors = [];
        foreach ($inputs as $inputName => $context) {
            $normalizedInputName = strtolower($inputName);
            if (isset($seenInputs[$normalizedInputName])) {
                $errors[] = \sprintf('Modifier input "%s" is provided more than once with different casing.', $inputName);
                continue;
            }
            $seenInputs[$normalizedInputName] = true;
            if (\is_int($context) || \is_float($context)) {
                $context = (string) $context;
            }
            if (!\is_string($context)) {
                $errors[] = \sprintf('Input "%s" must be a string or a number.', $inputName);
                continue;
            }
            $modifier = $normalizedModifiers[$normalizedInputName] ?? null;
            if (null === $modifier) {
                $errors[] = \sprintf('Unknown modifier "%s".', $inputName);
                continue;
            }
            $provided[$modifier['name']] = true;
            $contexts = $this->contexts($modifier['item'], $modifier['name']);
            $contextName = $this->caseInsensitiveKey($contexts, $context);
            if (null === $contextName) {
                $errors[] = \sprintf('Invalid context "%s" for modifier "%s".', $context, $modifier['name']);
                continue;
            }
            $selected[$modifier['name']] = $contextName;
        }

        foreach ($modifiers as $name => $modifier) {
            if (!isset($provided[$name]) && !\array_key_exists('default', $modifier)) {
                $errors[] = \sprintf('Missing required modifier "%s".', $name);
            }
        }

        if ([] !== $errors) {
            throw new ResolverException($errors);
        }

        return $selected;
    }

    /**
     * @param array<array-key, mixed> $item
     *
     * @return array<string, list<mixed>>
     */
    private function contexts(array $item, string $name): array
    {
        $contexts = $item['contexts'] ?? null;
        if (!\is_array($contexts) || [] === $contexts || array_is_list($contexts)) {
            throw new InvalidArgumentException(\sprintf('Modifier "%s" must declare a non-empty contexts object.', $name));
        }
        $normalizedContexts = [];
        $validContexts = [];
        foreach ($contexts as $context => $sources) {
            // PHP turns a context named "320" into the int key 320.
            $context = (string) $context;
            if ('' === $context || !\is_array($sources) || !array_is_list($sources)) {
                throw new InvalidArgumentException(\sprintf('Context "%s" of modifier "%s" must be an array of token sources.', $context, $name));
            }
            $normalizedContext = strtolower($context);
            if (isset($normalizedContexts[$normalizedContext])) {
                throw new InvalidArgumentException(\sprintf('Context names "%s" and "%s" of modifier "%s" differ only by case.', $normalizedContexts[$normalizedContext], $context, $name));
            }
            $normalizedContexts[$normalizedContext] = $context;
            $this->validateSources($sources, 'modifier', $name.'.'.$context);
            $validContexts[$context] = $sources;
        }
        if (1 === \count($contexts)) {
            throw new InvalidArgumentException(\sprintf('Modifier "%s" must declare at least two contexts; use a set for unconditional tokens.', $name));
        }

        return $validContexts;
    }

    /** @param array<array-key, mixed> $item */
    private function defaultContext(array $item, string $name): string
    {
        $default = $item['default'] ?? null;
        \assert(\is_string($default));

        return $default;
    }

    /**
     * @param list<mixed>      $rawSources
     * @param 'set'|'modifier' $ownerType
     * @param list<string>     $stack
     *
     * @return list<ResolverSource>
     */
    private function expandSources(array $rawSources, string $ownerType, array $stack, string $basePath): array
    {
        $result = [];
        foreach ($rawSources as $index => $source) {
            if (!\is_array($source) || array_is_list($source)) {
                throw new InvalidArgumentException(\sprintf('Token source %d must be an object.', $index));
            }
            /** @var array<string, mixed> $source */
            if (!\array_key_exists('$ref', $source)) {
                $result[] = new ResolverSource($source, $basePath);
                continue;
            }

            $ref = $this->referenceString($source, 'token source');
            $this->assertAllowedSourceReference($ref, $ownerType);
            $resolved = $this->resolveReference($source, $ownerType, $stack, $basePath, $this->data);
            $sourceBasePath = $this->referenceBasePath($ref, $basePath);

            if (isset($resolved['sources']) && \is_array($resolved['sources']) && array_is_list($resolved['sources'])) {
                foreach ($this->expandSources($resolved['sources'], $ownerType, [...$stack, $this->referenceKey($ref, $basePath)], $sourceBasePath) as $nested) {
                    $result[] = $nested;
                }
                continue;
            }

            $result[] = new ResolverSource($resolved, $sourceBasePath, $ref);
        }

        return $result;
    }

    /**
     * @param array<array-key, mixed>            $reference
     * @param 'set'|'modifier'|'resolutionOrder' $ownerType
     * @param list<string>                       $stack
     * @param array<array-key, mixed>            $documentData
     *
     * @return array<array-key, mixed>
     */
    private function resolveReference(array $reference, string $ownerType, array $stack, string $basePath, array $documentData, string $documentUri = ''): array
    {
        $ref = $this->referenceString($reference, 'reference object');
        if (str_starts_with($ref, '#/resolutionOrder/')) {
            throw new InvalidArgumentException(\sprintf('Resolver references must not point into resolutionOrder: "%s".', $ref));
        }
        if ('resolutionOrder' !== $ownerType && str_starts_with($ref, '#/modifiers/')) {
            throw new InvalidArgumentException(\sprintf('%ss must not reference modifiers: "%s".', ucfirst($ownerType), $ref));
        }
        $parts = explode('#', $ref, 2);
        $uri = $parts[0];
        $fragment = $parts[1] ?? null;
        $targetUri = '' === $uri ? $documentUri : $this->absoluteUri($uri, $basePath);
        $key = $this->referenceKey($ref, $basePath, $documentUri);
        if (\in_array($key, $stack, true)) {
            throw new InvalidArgumentException(\sprintf('Circular resolver reference detected: %s -> %s.', implode(' -> ', $stack), $key));
        }
        if ('' === $uri) {
            $target = $this->pointer($documentData, $fragment, $ref);
            $targetDocument = $documentData;
            $targetBasePath = $basePath;
        } else {
            $targetDocument = $this->loadDocument($this->absoluteUri($uri, $basePath));
            $target = $targetDocument;
            if (null !== $fragment && '' !== $fragment) {
                $target = $this->pointer($target, $fragment, $ref);
            }
            $targetBasePath = $this->referenceBasePath($ref, $basePath);
        }

        if (\array_key_exists('$ref', $target)) {
            $target = $this->resolveReference($target, $ownerType, [...$stack, $key], $targetBasePath, $targetDocument, $targetUri);
        }

        $overrides = $reference;
        unset($overrides['$ref']);

        return array_replace($target, $overrides);
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    private function pointer(array $data, ?string $fragment, string $ref): array
    {
        if (null === $fragment || '' === $fragment) {
            return $data;
        }
        if (!str_starts_with($fragment, '/')) {
            throw new InvalidArgumentException(\sprintf('Resolver reference fragment must be a JSON Pointer: "%s".', $ref));
        }

        $current = $data;
        foreach (JsonPointer::segments($fragment) as $segment) {
            if (!\array_key_exists($segment, $current) || !\is_array($current[$segment]) || ([] !== $current[$segment] && array_is_list($current[$segment]))) {
                throw new InvalidArgumentException(\sprintf('Resolver pointer not found: "%s".', $ref));
            }
            /** @var array<string, mixed> $next */
            $next = $current[$segment];
            $current = $next;
        }

        return $current;
    }

    /** @param array<array-key, mixed> $reference */
    private function referenceString(array $reference, string $location): string
    {
        $ref = $reference['$ref'] ?? null;
        if (!\is_string($ref) || '' === $ref) {
            throw new InvalidArgumentException(\sprintf('The $ref in %s must be a non-empty string.', $location));
        }

        return $ref;
    }

    /**
     * @param array<array-key, mixed> $target
     *
     * @return 'set'|'modifier'
     */
    private function referencedItemType(string $ref, array $target): string
    {
        if (preg_match('~(?:^|#)/sets/[^/]+$~', $ref)) {
            return 'set';
        }
        if (preg_match('~(?:^|#)/modifiers/[^/]+$~', $ref)) {
            return 'modifier';
        }
        $type = $target['type'] ?? null;
        if (!\is_string($type) || !\in_array($type, ['set', 'modifier'], true)) {
            throw new InvalidArgumentException(\sprintf('resolutionOrder reference "%s" does not identify a set or modifier.', $ref));
        }

        return $type;
    }

    /** @param array<array-key, mixed> $target */
    private function referencedItemName(string $ref, array $target): string
    {
        if (preg_match('~(?:^|#)/(?:sets|modifiers)/([^/]+)$~', $ref, $matches)) {
            return JsonPointer::segments('/'.$matches[1])[0];
        }
        $name = $target['name'] ?? null;
        if (!\is_string($name) || '' === $name) {
            throw new InvalidArgumentException(\sprintf('Referenced resolutionOrder item "%s" must have a name.', $ref));
        }

        return $name;
    }

    /** @param array<array-key, mixed> $item @param 'set'|'modifier' $type */
    private function validateItem(array $item, string $type, string $name, bool $allowIdentity = false): void
    {
        $this->validateOptionalMetadata($item, ucfirst($type).' "'.$name.'"');
        $identityProperties = $allowIdentity ? ['name', 'type'] : [];

        if ('set' === $type) {
            $this->assertOnlyProperties($item, ['description', 'sources', '$extensions', ...$identityProperties], \sprintf('Set "%s"', $name));
            if (!isset($item['sources']) || !\is_array($item['sources']) || !array_is_list($item['sources'])) {
                throw new InvalidArgumentException(\sprintf('Set "%s" must declare a sources array.', $name));
            }
            /** @var list<mixed> $sources */
            $sources = $item['sources'];
            $this->validateSources($sources, 'set', $name);

            return;
        }

        $this->assertOnlyProperties($item, ['description', 'contexts', 'default', '$extensions', ...$identityProperties], \sprintf('Modifier "%s"', $name));
        $contexts = $this->contexts($item, $name);
        if (\array_key_exists('default', $item)) {
            $default = $item['default'];
            if (!\is_string($default) || !\array_key_exists($default, $contexts)) {
                throw new InvalidArgumentException(\sprintf('Default context for modifier "%s" must match a context key.', $name));
            }
        }
    }

    private function assertAllowedSourceReference(string $ref, string $ownerType): void
    {
        $fragment = str_contains($ref, '#') ? '#'.explode('#', $ref, 2)[1] : '';
        if (str_starts_with($fragment, '#/resolutionOrder/')) {
            throw new InvalidArgumentException(\sprintf('Resolver references must not point into resolutionOrder: "%s".', $ref));
        }
        if (str_starts_with($fragment, '#/modifiers/')) {
            throw new InvalidArgumentException(\sprintf('%ss must not reference modifiers: "%s".', ucfirst($ownerType), $ref));
        }
    }

    private function referenceBasePath(string $ref, string $currentBasePath): string
    {
        $uri = explode('#', $ref, 2)[0];
        if ('' === $uri) {
            return $currentBasePath;
        }
        $directory = \dirname($this->absoluteUri($uri, $currentBasePath));

        return '.' === $directory ? '' : $directory;
    }

    private function referenceKey(string $ref, string $basePath, string $documentUri = ''): string
    {
        $parts = explode('#', $ref, 2);

        return ('' === $parts[0] ? $documentUri : $this->absoluteUri($parts[0], $basePath)).'#'.($parts[1] ?? '');
    }

    /** @return array<array-key, mixed> */
    private function loadDocument(string $uri): array
    {
        return $this->documents[$uri] ??= ($this->loader ?? new JsonDocumentLoader())->load($uri);
    }

    private function absoluteUri(string $uri, string $basePath): string
    {
        if ('' === $basePath || Path::isAbsolute($uri) || 1 === preg_match('#^[a-z][a-z0-9+.-]*://#i', $uri)) {
            return $uri;
        }

        return Path::join($basePath, $uri);
    }

    /** @param array<string, mixed> $contexts */
    private function caseInsensitiveKey(array $contexts, string $input): ?string
    {
        foreach (array_keys($contexts) as $context) {
            if (0 === strcasecmp($context, $input)) {
                return $context;
            }
        }

        return null;
    }

    private function validateDocument(): void
    {
        if (($this->data['version'] ?? null) !== self::VERSION) {
            throw new InvalidArgumentException('A DTCG Resolver document must declare version "2025.10".');
        }
        if (!\is_array($this->data['resolutionOrder'] ?? null) || !array_is_list($this->data['resolutionOrder']) || [] === $this->data['resolutionOrder']) {
            throw new InvalidArgumentException('A DTCG Resolver document must define a non-empty resolutionOrder array.');
        }
        $this->assertOnlyProperties($this->data, ['$schema', 'name', 'version', 'description', 'sets', 'modifiers', 'resolutionOrder', '$defs'], 'Resolver document');
        foreach (['name', 'description', '$schema'] as $property) {
            if (isset($this->data[$property]) && !\is_string($this->data[$property])) {
                throw new InvalidArgumentException(\sprintf('Resolver property "%s" must be a string.', $property));
            }
        }
        foreach (['sets', 'modifiers', '$defs'] as $property) {
            if (isset($this->data[$property]) && (!\is_array($this->data[$property]) || ([] !== $this->data[$property] && array_is_list($this->data[$property])))) {
                throw new InvalidArgumentException(\sprintf('Resolver property "%s" must be an object.', $property));
            }
        }

        /** @var array<string, mixed> $sets */
        $sets = $this->data['sets'] ?? [];
        foreach ($sets as $name => $set) {
            $name = (string) $name;
            if ('' === $name || !\is_array($set) || array_is_list($set)) {
                throw new InvalidArgumentException('Every resolver set must be a named object.');
            }
            $this->validateItem($set, 'set', $name);
        }
        /** @var array<string, mixed> $modifiers */
        $modifiers = $this->data['modifiers'] ?? [];
        foreach ($modifiers as $name => $modifier) {
            $name = (string) $name;
            if ('' === $name || !\is_array($modifier) || array_is_list($modifier)) {
                throw new InvalidArgumentException('Every resolver modifier must be a named object.');
            }
            $this->validateItem($modifier, 'modifier', $name);
        }

        $this->resolutionOrder();
    }

    /**
     * @param list<mixed>      $sources
     * @param 'set'|'modifier' $ownerType
     */
    private function validateSources(array $sources, string $ownerType, string $ownerName): void
    {
        foreach ($sources as $position => $source) {
            if (!\is_array($source) || array_is_list($source)) {
                throw new InvalidArgumentException(\sprintf('Token source %d of %s "%s" must be an object.', $position, $ownerType, $ownerName));
            }
            /** @var array<string, mixed> $source */
            if (\array_key_exists('$ref', $source)) {
                $ref = $this->referenceString($source, \sprintf('token source %d of %s "%s"', $position, $ownerType, $ownerName));
                $this->assertAllowedSourceReference($ref, $ownerType);

                // Resolver 4.2.1: an invalid pointer is an error even where nothing selects it.
                if (str_starts_with($ref, '#')) {
                    $this->pointer($this->data, substr($ref, 1), $ref);
                }
            }
        }
    }

    /** @param array<array-key, mixed> $item */
    private function validateOptionalMetadata(array $item, string $location): void
    {
        if (\array_key_exists('description', $item) && !\is_string($item['description'])) {
            throw new InvalidArgumentException(\sprintf('%s property "description" must be a string.', $location));
        }
        if (\array_key_exists('$extensions', $item) && (!\is_array($item['$extensions']) || ([] !== $item['$extensions'] && array_is_list($item['$extensions'])))) {
            throw new InvalidArgumentException(\sprintf('%s property "$extensions" must be an object.', $location));
        }
    }

    /**
     * @param array<array-key, mixed> $item
     * @param list<string>            $allowed
     */
    private function assertOnlyProperties(array $item, array $allowed, string $location): void
    {
        foreach (array_keys($item) as $property) {
            if (!\in_array($property, $allowed, true)) {
                throw new InvalidArgumentException(\sprintf('%s contains unsupported property "%s".', $location, $property));
            }
        }
    }
}
