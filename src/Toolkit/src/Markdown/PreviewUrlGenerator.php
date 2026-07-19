<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown;

/**
 * Host-implemented contract that turns a code snippet into a live-preview URL.
 *
 * The Toolkit ships no implementation on purpose: rendering a live preview is host-specific
 * (a website signs a URL to a controller; a local CLI serves it from its own server). When no
 * generator is provided, {@see Extension\CodePreview\Renderer\CodePreviewRenderer} falls back to
 * a static, non-interactive code block.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
interface PreviewUrlGenerator
{
    /**
     * @return string|null The URL to embed as a live preview of $code, or null to fall back to a static block
     */
    public function generate(string $code, CodeOptions $options): ?string;
}
