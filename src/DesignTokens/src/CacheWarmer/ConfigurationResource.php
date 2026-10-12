<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\CacheWarmer;

use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Config\ResourceCheckerInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class ConfigurationResource implements ResourceInterface, ResourceCheckerInterface
{
    public function __construct(
        private readonly string $fingerprint,
    ) {
    }

    public function __toString(): string
    {
        return 'ux_design_tokens.configuration.'.$this->fingerprint;
    }

    public function supports(ResourceInterface $metadata): bool
    {
        return $metadata instanceof self;
    }

    public function isFresh(ResourceInterface $resource, int $timestamp): bool
    {
        return $resource instanceof self && $resource->fingerprint === $this->fingerprint;
    }
}
