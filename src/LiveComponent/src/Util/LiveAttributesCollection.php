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

    public function setLiveController(string $componentName): void
    {
        $this->attributes['data-controller'] = 'live';
        $this->attributes['data-live-name-value'] = $componentName;
    }

    public function setLiveId(string $id): void
    {
        $this->attributes['data-live-id'] = $id;
    }

    public function setFingerprint(string $fingerprint): void
    {
        $this->attributes['data-live-fingerprint-value'] = $fingerprint;
    }

    public function setProps(array $dehydratedProps): void
    {
        $this->attributes['data-live-props-value'] = $dehydratedProps;
    }

    public function getProps(): array
    {
        if (!\array_key_exists('data-live-props-value', $this->attributes)) {
            throw new \LogicException('You must call setProps() before calling getProps().');
        }

        return $this->attributes['data-live-props-value'];
    }

    public function setUrl(string $url): void
    {
        $this->attributes['data-live-url-value'] = $url;
    }

    public function setCsrf(string $csrf): void
    {
        $this->attributes['data-live-csrf-value'] = $csrf;
    }

    public function setListeners(array $listeners): void
    {
        $this->attributes['data-live-listeners-value'] = $listeners;
    }

    public function setEventsToEmit(array $events): void
    {
        $this->attributes['data-live-emit'] = $events;
    }

    public function setBrowserEventsToDispatch(array $browserEventsToDispatch): void
    {
        $this->attributes['data-live-browser-dispatch'] = $browserEventsToDispatch;
    }

    private function escapeAttribute(string $value): string
    {
        return twig_escape_filter($this->twig, $value, 'html_attr');
    }
}
