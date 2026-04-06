<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Config;

final class BridgeCapabilities
{
    /**
     * @param list<'html'|'blocks'|'page'> $supportedFormats
     */
    public function __construct(
        public readonly bool $supportsToolbar,
        public readonly bool $supportsPlugins,
        public readonly bool $supportsTheme,
        public readonly bool $supportsLanguage,
        public readonly array $supportedFormats,
    ) {
    }

    /**
     * @param list<'html'|'blocks'|'page'>|null $supportedFormats
     */
    public function with(
        ?bool $supportsToolbar = null,
        ?bool $supportsPlugins = null,
        ?bool $supportsTheme = null,
        ?bool $supportsLanguage = null,
        ?array $supportedFormats = null,
    ): self {
        return new self(
            $supportsToolbar ?? $this->supportsToolbar,
            $supportsPlugins ?? $this->supportsPlugins,
            $supportsTheme ?? $this->supportsTheme,
            $supportsLanguage ?? $this->supportsLanguage,
            $supportedFormats ?? $this->supportedFormats,
        );
    }
}
