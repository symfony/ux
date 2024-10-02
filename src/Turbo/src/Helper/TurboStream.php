<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Helper;

/**
 * @see https://turbo.hotwired.dev/reference/streams
 */
final class TurboStream
{
    /**
     * Appends to the element(s) designated by the target CSS selector.
     */
    public static function append(string $target, string $html): string
    {
        return self::wrap('append', $target, $html);
    }

    /**
     * Prepends to the element(s) designated by the target CSS selector.
     */
    public static function prepend(string $target, string $html): string
    {
        return self::wrap('prepend', $target, $html);
    }

    /**
     * Replaces the element(s) designated by the target CSS selector.
     */
    public static function replace(string $target, string $html, bool $morph = false): string
    {
        return self::wrap('replace', $target, $html, $morph ? ' method="morph"' : '');
    }

    /**
     * Updates the content of the element(s) designated by the target CSS selector.
     */
    public static function update(string $target, string $html, bool $morph = false): string
    {
        return self::wrap('update', $target, $html, $morph ? ' method="morph"' : '');
    }

    /**
     * Removes the element(s) designated by the target CSS selector.
     */
    public static function remove(string $target): string
    {
        return \sprintf('<turbo-stream action="remove" targets="%s"></turbo-stream>', htmlspecialchars($target));
    }

    /**
     * Inserts before the element(s) designated by the target CSS selector.
     */
    public static function before(string $target, string $html): string
    {
        return self::wrap('before', $target, $html);
    }

    /**
     * Inserts after the element(s) designated by the target CSS selector.
     */
    public static function after(string $target, string $html): string
    {
        return self::wrap('after', $target, $html);
    }

    /**
     * Initiates a Page Refresh to render new content with morphing.
     *
     * @see Initiates a Page Refresh to render new content with morphing.
     */
    public static function refresh(?string $requestId = null): string
    {
        if (null === $requestId) {
            return '<turbo-stream action="refresh"></turbo-stream>';
        }

        return \sprintf('<turbo-stream action="refresh" request-id="%s"></turbo-stream>', htmlspecialchars($requestId));
    }

    private static function wrap(string $action, string $target, string $html, string $attr = ''): string
    {
        return \sprintf(<<<EOHTML
            <turbo-stream action="%s" targets="%s"%s>
                <template>%s</template>
            </turbo-stream>
            EOHTML, $action, htmlspecialchars($target), $attr, $html);
    }
}
