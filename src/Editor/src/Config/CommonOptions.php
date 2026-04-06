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

final class CommonOptions
{
    public function __construct(
        public readonly ?array $toolbar = null,
        public readonly ?string $placeholder = null,
        public readonly bool $readOnly = false,
        public readonly ?string $height = null,
        public readonly ?string $theme = null,
        public readonly ?string $language = null,
        public readonly array $plugins = [],
        public readonly bool $autofocus = false,
        public readonly bool $spellcheck = true,
    ) {
    }

    public static function fromArray(array $a): self
    {
        return new self(
            toolbar: $a['toolbar'] ?? null,
            placeholder: $a['placeholder'] ?? null,
            readOnly: (bool) ($a['readOnly'] ?? false),
            height: $a['height'] ?? null,
            theme: $a['theme'] ?? null,
            language: $a['language'] ?? null,
            plugins: $a['plugins'] ?? [],
            autofocus: (bool) ($a['autofocus'] ?? false),
            spellcheck: (bool) ($a['spellcheck'] ?? true),
        );
    }
}
