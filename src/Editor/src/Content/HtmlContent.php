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

use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

final class HtmlContent extends EditorContent
{
    public function __construct(public readonly string $html, array $metadata = [])
    {
        parent::__construct(EditorContentFormat::Html, $metadata);
    }

    public function getRaw(): string
    {
        return $this->html;
    }

    public function isEmpty(): bool
    {
        return trim(strip_tags($this->html)) === '';
    }

    public function getSanitized(?HtmlSanitizerInterface $sanitizer = null): string
    {
        return $sanitizer === null ? $this->html : $sanitizer->sanitize($this->html);
    }

    public static function fromString(string $html, array $metadata = []): self
    {
        return new self($html, $metadata);
    }
}
