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
 * A token source together with the directory its relative references use.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final readonly class ResolverSource
{
    /** @param array<array-key, mixed> $tokens */
    public function __construct(
        public array $tokens,
        public string $basePath,
        public ?string $uri = null,
    ) {
    }
}
