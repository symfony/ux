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

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\DesignTokens\Exception\RuntimeException;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class JsonDocumentLoader implements DocumentLoaderInterface
{
    /** @var list<string> */
    private readonly array $allowedRoots;

    /**
     * @param string       $basePath     directory prepended to relative URIs
     * @param list<string> $allowedRoots directories a document may be read from, or an empty list to read anywhere
     */
    public function __construct(
        private readonly string $basePath = '',
        array $allowedRoots = [],
    ) {
        $this->allowedRoots = array_map(static fn (string $root): string => Path::canonicalize(realpath($root) ?: $root), $allowedRoots);
    }

    public function load(string $uri): array
    {
        $path = $this->guard($this->resolvePath($uri));

        if (!is_file($path)) {
            throw new RuntimeException(\sprintf('Design token file not found: "%s".', $path));
        }

        try {
            $json = new Filesystem()->readFile($path);
        } catch (IOExceptionInterface $e) {
            throw new RuntimeException(\sprintf('Could not read design token file: "%s".', $path), previous: $e);
        }

        try {
            $data = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException(\sprintf('Invalid JSON in design token file "%s": %s', $path, $e->getMessage()), 0, $e);
        }
        // Decoded into arrays, {"0": ...} and [...] look alike, so the JSON text decides.
        if (!\is_array($data) || !str_starts_with(ltrim($json), '{')) {
            throw new RuntimeException(\sprintf('Design token file must contain a JSON object: "%s".', $path));
        }

        return $data;
    }

    private function resolvePath(string $uri): string
    {
        if ('' !== $this->basePath && !Path::isAbsolute($uri)) {
            return Path::join($this->basePath, $uri);
        }

        return $uri;
    }

    /** Keeps a `$ref` inside the allowed roots, symlinks resolved. */
    private function guard(string $path): string
    {
        if ([] === $this->allowedRoots) {
            return $path;
        }

        if (false === $real = realpath($path)) {
            return $path;
        }

        $real = Path::canonicalize($real);
        foreach ($this->allowedRoots as $root) {
            if (Path::isBasePath($root, $real)) {
                return $path;
            }
        }

        throw new RuntimeException(\sprintf('Refusing to read design token document "%s": it resolves outside %s. Decorate "%s" to read documents from elsewhere.', $path, implode(', ', array_map(static fn (string $root): string => '"'.$root.'"', $this->allowedRoots)), DocumentLoaderInterface::class));
    }
}
