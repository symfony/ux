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
 * An array of attributes that can eventually be returned as an escaped array.
 *
 * @experimental
 *
 * @internal
 */
final class LiveAttributesCollection
{
    private array $attributes = [];

    public function __construct(private Environment $twig)
    {
    }

    public function toEscapedArray(): array
    {
        $escaped = [];
        foreach ($this->attributes as $key => $value) {
            if (\is_array($value)) {
                $value = JsonUtil::encodeObject($value);
            }

            $escaped[$key] = $this->escapeAttribute($value);
        }

        return $escaped;
    }

    public function addLiveController(): void
    {
        $this->attributes['data-controller'] = 'live';
    }

    public function addLiveId(string $id): void
    {
        $this->attributes['data-live-id'] = $id;
    }

    public function addFingerprint(string $fingerprint): void
    {
        $this->attributes['data-live-fingerprint-value'] = $fingerprint;
    }

    public function addProps(array $dehydratedProps): void
    {
        $this->attributes['data-live-props-value'] = $dehydratedProps;
    }

    public function addNestedProps(array $nestedProps): void
    {
        $this->attributes['data-live-nested-props-value'] = $nestedProps;
    }

    public function getProps(): array
    {
        if (!\array_key_exists('data-live-props-value', $this->attributes)) {
            throw new \LogicException('You must call addProps() before calling getProps().');
        }

        return $this->attributes['data-live-props-value'];
    }

    public function addLiveUrl(string $url): void
    {
        $this->attributes['data-live-url-value'] = $url;
    }

    public function addLiveCsrf(string $csrf): void
    {
        $this->attributes['data-live-csrf-value'] = $csrf;
    }

    private function escapeAttribute(string $value): string
    {
        return twig_escape_filter($this->twig, $value, 'html_attr');
    }
}
