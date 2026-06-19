<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons;

/**
 * Creates sanitized {@see Icon} instances from SVG files or raw SVG bodies.
 *
 * SVG content can come from untrusted sources (the Iconify API, or local files
 * populated by user uploads) and ends up rendered as raw HTML. To prevent XSS,
 * script-capable elements and attributes are stripped from the SVG tree.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class IconFactory
{
    private const FORBIDDEN_ELEMENTS = ['script', 'foreignobject', 'iframe', 'object', 'embed', 'handler'];
    private const ANIMATION_ELEMENTS = ['animate', 'set', 'animatetransform', 'animatemotion'];

    public function fromFile(string $filename): Icon
    {
        if (!class_exists(\DOMDocument::class)) {
            throw new \LogicException('The "DOM" PHP extension is required to create icons from files.');
        }

        $svg = file_get_contents($filename) ?: throw new \RuntimeException(\sprintf('The icon file "%s" could not be read.', $filename));

        $document = new \DOMDocument();
        $document->preserveWhiteSpace = false;

        try {
            $document->loadXML($svg, \LIBXML_NONET);
        } catch (\Throwable $e) {
            throw new \RuntimeException(\sprintf('The icon file "%s" does not contain a valid SVG.', $filename), previous: $e);
        }

        $svgElement = $this->singleSvgElement($document, $filename);

        $this->sanitizeElement($svgElement);

        $innerSvg = $this->innerSvg($document, $svgElement);

        if ('' === $innerSvg) {
            throw new \RuntimeException(\sprintf('The icon file "%s" contains an empty SVG.', $filename));
        }

        $attributes = array_map(static fn (\DOMAttr $a) => $a->value, [...$svgElement->attributes]);

        return new Icon($innerSvg, $attributes);
    }

    /**
     * @param array<string, string|bool> $attributes
     */
    public function fromBody(string $body, array $attributes = []): Icon
    {
        if (!class_exists(\DOMDocument::class)) {
            throw new \LogicException('The "DOM" PHP extension is required to create icons.');
        }

        $document = new \DOMDocument();
        $wrapped = \sprintf('<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">%s</svg>', $body);

        try {
            $document->loadXML($wrapped, \LIBXML_NONET);
        } catch (\Throwable $e) {
            throw new \RuntimeException('The icon body is not valid SVG.', previous: $e);
        }

        if (null === $svgElement = $document->documentElement) {
            throw new \RuntimeException('The icon body is not valid SVG.');
        }

        // Re-serializing is lossy: when the body is already safe, keep it untouched so
        // benign icons stay byte-for-byte identical (and the cached value stays stable).
        if (!$this->sanitizeElement($svgElement)) {
            return new Icon($body, $attributes);
        }

        return new Icon($this->innerSvg($document, $svgElement), $attributes);
    }

    /**
     * Removes script-capable elements and attributes from $element and its descendants.
     *
     * @return bool whether anything was removed
     */
    private function sanitizeElement(\DOMElement $element): bool
    {
        $modified = $this->sanitizeAttributes($element);

        // \DOMNodeList is live, so collect before mutating.
        $children = [];
        $rawNodes = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $children[] = $child;
            } elseif ($child instanceof \DOMCdataSection || $child instanceof \DOMProcessingInstruction) {
                // The tree is parsed as XML but serialized as HTML: saveHTML() emits the raw
                // payload of CDATA sections and processing instructions verbatim, turning
                // "<![CDATA[<img onerror=...>]]>" into live markup (mutation XSS). Drop them.
                $rawNodes[] = $child;
            }
        }
        foreach ($rawNodes as $child) {
            $element->removeChild($child);
            $modified = true;
        }
        foreach ($children as $child) {
            $name = strtolower($child->localName);

            if (\in_array($name, self::FORBIDDEN_ELEMENTS, true)
                || (\in_array($name, self::ANIMATION_ELEMENTS, true) && $this->animatesDangerousAttribute($child))
            ) {
                $element->removeChild($child);
                $modified = true;

                continue;
            }

            if ('style' === $name) {
                // <style> is kept (themes rely on it, e.g. prefers-color-scheme), but it is a
                // raw-text element: saveHTML() re-emits its CSS verbatim, so the only XSS path is
                // a "</style>" breakout smuggled through CDATA. Sanitize the attributes, never the
                // CSS itself, and drop the whole element on a breakout attempt.
                if ($this->sanitizeAttributes($child)) {
                    $modified = true;
                }
                if (preg_match('#</\s*style#i', $child->textContent)) {
                    $element->removeChild($child);
                    $modified = true;
                }

                continue;
            }

            if ($this->sanitizeElement($child)) {
                $modified = true;
            }
        }

        return $modified;
    }

    private function sanitizeAttributes(\DOMElement $element): bool
    {
        $modified = false;

        // \DOMNamedNodeMap is live, so collect before mutating.
        $dangerous = [];
        foreach ($element->attributes as $attribute) {
            if ($this->isDangerousAttribute($attribute->nodeName, $attribute->value)) {
                $dangerous[] = $attribute;
            }
        }
        foreach ($dangerous as $attribute) {
            $element->removeAttributeNode($attribute);
            $modified = true;
        }

        return $modified;
    }

    private function singleSvgElement(\DOMDocument $document, string $filename): \DOMElement
    {
        $svgElements = $document->getElementsByTagName('svg');

        if (0 === $svgElements->length) {
            throw new \RuntimeException(\sprintf('The icon file "%s" does not contain a valid SVG.', $filename));
        }

        if (1 !== $svgElements->length) {
            throw new \RuntimeException(\sprintf('The icon file "%s" contains more than one SVG.', $filename));
        }

        return $svgElements->item(0) ?? throw new \RuntimeException(\sprintf('The icon file "%s" does not contain a valid SVG.', $filename));
    }

    private function innerSvg(\DOMDocument $document, \DOMElement $svgElement): string
    {
        $innerSvg = '';
        foreach ($svgElement->childNodes as $node) {
            // Ignore comments and text nodes.
            if ($node instanceof \DOMComment || $node instanceof \DOMText) {
                continue;
            }

            $innerSvg .= $document->saveHTML($node);
        }

        return $innerSvg;
    }

    private function animatesDangerousAttribute(\DOMElement $element): bool
    {
        $attributeName = strtolower($element->getAttribute('attributeName'));

        // A SMIL animation can install an event handler (on*) or a script-capable URL
        // (href/xlink:href -> "javascript:...") onto its target element.
        return str_starts_with($attributeName, 'on')
            || 'href' === $attributeName
            || 'xlink:href' === $attributeName;
    }

    private function isDangerousAttribute(string $name, string $value): bool
    {
        $name = strtolower($name);

        // Event handlers (onload, onclick, onbegin, ...).
        if (str_starts_with($name, 'on')) {
            return true;
        }

        if ('href' === $name || 'xlink:href' === $name) {
            // Strip whitespace and control characters used to obfuscate the scheme.
            $url = strtolower(preg_replace('/[\s\x00-\x1F]+/', '', $value) ?? '');

            // Scheme-less references (relative paths, "#fragment" used by <use> sprites) are safe.
            if (!preg_match('#^[a-z][a-z0-9+.-]*:#', $url)) {
                return false;
            }

            // Allowlist known-safe schemes; everything else (javascript:, vbscript:,
            // data:text/html, data:image/svg+xml, ...) is treated as dangerous.
            if (preg_match('#^(?:https?|mailto|tel):#', $url)) {
                return false;
            }

            // data: URLs only for non-script raster image types.
            return !preg_match('#^data:image/(?:png|jpe?g|gif|webp|bmp|avif)(?:[;,]|$)#', $url);
        }

        return false;
    }
}
