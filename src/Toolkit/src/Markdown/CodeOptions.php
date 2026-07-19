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
 * The typed options a `{"preview": true}` fenced code block declares: the live-preview iframe `height`
 * and whether the code tab collapses long `class` attributes. Parsed once from the block's info-string
 * JSON and threaded down the preview pipeline (CodePreview, PreviewUrlGenerator) instead of a loose array.
 *
 * It owns the Toolkit's slice of the info-string contract at both ends — the read (`fromInfoJson`) and
 * the code-tab write (`toCodeTabInfoJson`); the wider info-string vocabulary (e.g. a host `filename`) is
 * not modelled here. Pure PHP — no league/commonmark dependency.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class CodeOptions
{
    public function __construct(
        public readonly string $height = '200px',
        public readonly bool $collapseClass = false,
    ) {
    }

    /**
     * Builds the options from a fenced block's `{json}` info string, or null when the block does not opt
     * into a preview (`"preview": true` absent, or malformed JSON).
     */
    public static function fromInfoJson(string $json): ?self
    {
        $options = json_decode($json, true);
        if (!\is_array($options) || true !== ($options['preview'] ?? null)) {
            return null;
        }

        return new self(
            // Omit height when it isn't a string so the constructor default applies (defined once).
            ...(\is_string($options['height'] ?? null) ? ['height' => $options['height']] : []),
            collapseClass: (bool) ($options['collapseClass'] ?? false),
        );
    }

    /**
     * The code-tab styling options as a fenced info-string JSON fragment (a subset: `height` is a
     * Preview-tab concern and stays out), or null when there is nothing to carry. Symmetric with
     * {@see self::fromInfoJson()} so encode and decode of the format live together.
     */
    public function toCodeTabInfoJson(): ?string
    {
        return $this->collapseClass ? json_encode(['collapseClass' => $this->collapseClass]) : null;
    }
}
