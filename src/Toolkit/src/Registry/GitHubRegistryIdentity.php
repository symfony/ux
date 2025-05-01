<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Registry;

/**
 * @internal
 *
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class GitHubRegistryIdentity
{
    /**
     * @param non-empty-string $authorName
     * @param non-empty-string $repositoryName
     * @param non-empty-string $version
     */
    private function __construct(
        public readonly string $authorName,
        public readonly string $repositoryName,
        public readonly string $version,
    ) {
    }

    public static function fromUrl(string $url): self
    {
        $matches = [];
        if (1 !== preg_match(GitHubRegistry::RE_GITHUB_KIT, $url, $matches)) {
            throw new \InvalidArgumentException('The kit name is invalid, it must be a valid GitHub kit name.');
        }

        return new self(
            $matches['authorName'] ?: throw new \InvalidArgumentException('Unable to extract the author name from the URL.'),
            $matches['repositoryName'] ?: throw new \InvalidArgumentException('Unable to extract the repository name from the URL.'),
            $matches['version'] ?? 'main',
        );
    }
}
