<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Util;

use Twig\Environment;

/**
 * @experimental
 *
 * @internal
 */
final class TwigAttributeHelper
{
    public function __construct(private Environment $twig)
    {
    }

    public function escapeAttribute(string $value): string
    {
        return twig_escape_filter($this->twig, $value, 'html_attr');
    }

    public function addLiveController(array &$attributes): void
    {
        $attributes['data-controller'] = 'live';
    }

    public function addLiveId(string $id, array &$attributes): void
    {
        $attributes['data-live-id'] = $this->escapeAttribute($id);
    }

    public function addFingerprint(string $fingerprint, array &$attributes): void
    {
        $attributes['data-live-fingerprint-value'] = $this->escapeAttribute($fingerprint);
    }

    public function addProps(array $dehydratedProps, array &$attributes): void
    {
        $attributes['data-live-props-value'] = $this->escapeAttribute(JsonUtil::encodeObject($dehydratedProps));
    }

    public function addLiveUrl(string $url, array &$attributes): void
    {
        $attributes['data-live-url-value'] = $this->escapeAttribute($url);
    }

    public function addLiveCsrf(string $csrf, array &$attributes): void
    {
        $attributes['data-live-csrf-value'] = $this->escapeAttribute($csrf);
    }
}
