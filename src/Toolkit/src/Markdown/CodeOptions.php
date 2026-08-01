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
 * The typed options a fenced code block declares in its `{json}` info string.
 *
 * It owns the Toolkit's slice of the info-string contract at both ends — the read (`fromInfoJson`) and
 * the write (`toInfoJson`): the file `filename` shown above the block and whether the code collapses long
 * `class` attributes. The `"preview": true` opt-in itself is a routing trigger owned by
 * {@see Extension\FencedCodePreview\FencedCodePreviewExtension}, not an
 * option modelled here. Pure PHP — no league/commonmark dependency.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class CodeOptions
{
    public function __construct(
        public readonly ?string $filename = null,
        public readonly bool $collapseClass = false,
    ) {
    }

    /**
     * Builds the options from a fenced block's `{json}` info string, or null when it carries no JSON
     * (or malformed JSON).
     */
    public static function fromInfoJson(string $json): ?self
    {
        $options = json_decode($json, true);

        return \is_array($options) ? self::fromDecoded($options) : null;
    }

    /**
     * Builds the options from an already-decoded info string, so a caller that has parsed the JSON for
     * its own check (e.g. the preview opt-in) does not decode it twice.
     *
     * @param array<string, mixed> $options
     */
    public static function fromDecoded(array $options): self
    {
        return new self(
            filename: \is_string($options['filename'] ?? null) ? $options['filename'] : null,
            collapseClass: (bool) ($options['collapseClass'] ?? false),
        );
    }

    /**
     * The options as a fenced info-string JSON fragment, or null when there is nothing to carry.
     * Symmetric with {@see self::fromInfoJson()} so encode and decode of the format live together.
     */
    public function toInfoJson(): ?string
    {
        $data = [];
        if (null !== $this->filename) {
            $data['filename'] = $this->filename;
        }
        if ($this->collapseClass) {
            $data['collapseClass'] = true;
        }

        return $data ? json_encode($data, \JSON_UNESCAPED_SLASHES) : null;
    }
}
