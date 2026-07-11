<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Content;

final class PageContent extends EditorContent
{
    public function __construct(
        public readonly string $html,
        public readonly string $css = '',
        public readonly array $assets = [],
        public readonly array $components = [],
        array $metadata = [],
    ) {
        parent::__construct(EditorContentFormat::Page, $metadata);
    }

    public function getRaw(): array
    {
        return ['html' => $this->html, 'css' => $this->css, 'components' => $this->components];
    }

    public function isEmpty(): bool
    {
        return '' === $this->html && [] === $this->components;
    }

    public function extractAssets(): array
    {
        return $this->assets;
    }

    public static function fromBundle(array $bundle, array $metadata = []): self
    {
        return new self(
            html: (string) ($bundle['html'] ?? ''),
            css: (string) ($bundle['css'] ?? ''),
            assets: $bundle['assets'] ?? [],
            components: $bundle['components'] ?? [],
            metadata: $metadata,
        );
    }
}
