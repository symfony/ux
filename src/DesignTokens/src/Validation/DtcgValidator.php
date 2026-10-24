<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Validation;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Exception\RuntimeException;
use Symfony\UX\DesignTokens\Resolver\DocumentLoaderInterface;
use Symfony\UX\DesignTokens\Resolver\ResolverDocument;
use Symfony\UX\DesignTokens\Resolver\ResolverSource;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class DtcgValidator
{
    private readonly ColorRangeInspector $colorRanges;

    public function __construct(
        private readonly TokenTreeBuilder $resolver,
        ?ColorRangeInspector $colorRanges = null,
        private readonly ?DocumentLoaderInterface $documentLoader = null,
    ) {
        $this->colorRanges = $colorRanges ?? new ColorRangeInspector();
    }

    /**
     * @param bool $partial whether a Resolver completes the document with other
     *                      sources, so that its references may point outside it
     *
     * @return list<string> warnings worth surfacing, the document is valid either way
     */
    public function validateFile(string $path, bool $partial = false): array
    {
        if (!is_file($path)) {
            throw new RuntimeException(\sprintf('Design token document not found: "%s".', $path));
        }
        try {
            $json = new Filesystem()->readFile($path);
        } catch (IOExceptionInterface $e) {
            throw new RuntimeException(\sprintf('Could not read design token document: "%s".', $path), previous: $e);
        }

        $kind = match (true) {
            str_ends_with($path, '.resolver.json') => 'resolver',
            str_ends_with($path, '.tokens.json'), str_ends_with($path, '.tokens') => 'tokens',
            default => null,
        };
        if (null === $kind) {
            throw new InvalidArgumentException(\sprintf('Expected a .tokens.json, .tokens, or .resolver.json file, got "%s".', $path));
        }

        return $this->validateJson($json, $path, \dirname($path), $kind, $partial);
    }

    /**
     * @param 'tokens'|'resolver'|null $kind
     * @param bool                     $partial see validateFile()
     *
     * @return list<string> warnings worth surfacing, the document is valid either way
     */
    public function validateJson(string $json, string $source = 'stdin', string $basePath = '', ?string $kind = null, bool $partial = false): array
    {
        try {
            // Decoded as objects only to tell a top-level {} from a [].
            $object = json_decode($json, false, 512, \JSON_THROW_ON_ERROR);
            $data = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw new RuntimeException(\sprintf('Invalid JSON in "%s": %s', $source, $error->getMessage()), 0, $error);
        }
        if (!$object instanceof \stdClass || !\is_array($data)) {
            throw new RuntimeException(\sprintf('DTCG document "%s" must be a JSON object at the top level.', $source));
        }

        return $this->validate($data, $source, $basePath, $kind, $partial);
    }

    /**
     * @param array<array-key, mixed>  $data
     * @param 'tokens'|'resolver'|null $kind
     * @param bool                     $partial see validateFile()
     *
     * @return list<string> warnings worth surfacing, the document is valid either way
     */
    public function validate(array $data, string $source = 'memory', string $basePath = '', ?string $kind = null, bool $partial = false): array
    {
        $kind ??= $this->looksLikeResolver($data) ? 'resolver' : 'tokens';
        if ('resolver' === $kind) {
            return $this->validateResolver($data, $basePath);
        }

        $source = new ResolverSource($data, $basePath, \in_array($source, ['stdin', 'memory'], true) ? null : $source);

        return $this->colorRanges->inspect($partial ? $this->resolver->resolvePartial($source) : $this->resolver->resolveSources([$source]));
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return list<string>
     */
    private function validateResolver(array $data, string $basePath): array
    {
        $warnings = [];
        $document = new ResolverDocument($data, $basePath, $this->documentLoader);
        foreach ($document->getPermutations() as $inputs) {
            $resolved = $this->resolver->resolveSources($document->sourceDescriptors($inputs));
            foreach ($this->colorRanges->inspect($resolved) as $warning) {
                $warnings[$warning] = true;
            }
        }

        return array_keys($warnings);
    }

    /** @param array<array-key, mixed> $data */
    private function looksLikeResolver(array $data): bool
    {
        return '2025.10' === ($data['version'] ?? null)
            && \is_array($data['resolutionOrder'] ?? null)
            && (\array_key_exists('sets', $data) || \array_key_exists('modifiers', $data));
    }
}
