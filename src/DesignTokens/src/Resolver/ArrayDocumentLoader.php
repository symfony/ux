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

use Symfony\UX\DesignTokens\Exception\RuntimeException;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
final class ArrayDocumentLoader implements DocumentLoaderInterface
{
    /** @param array<string, array<array-key, mixed>> $documents decoded documents keyed by URI */
    public function __construct(private readonly array $documents)
    {
    }

    public function load(string $uri): array
    {
        return $this->documents[$uri] ?? throw new RuntimeException(\sprintf('Design token document not found: "%s".', $uri));
    }
}
