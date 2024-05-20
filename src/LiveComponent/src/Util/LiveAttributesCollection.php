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
use Twig\Extension\EscaperExtension;
use Twig\Runtime\EscaperRuntime;

/**
 * An array of attributes that can eventually be returned as an escaped array.
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

    // TODO rename that
    public function setLiveId(string $id): void
    {
        $this->attributes['id'] = $id;
    }

    public function setFingerprint(string $fingerprint): void
    {
        $this->attributes['data-live-fingerprint-value'] = $fingerprint;
    }

    public function setProps(array $dehydratedProps): void
    {
        $this->attributes['data-live-props-value'] = $dehydratedProps;
    }

    public function setPropsUpdatedFromParent(array $dehydratedProps): void
    {
        $this->attributes['data-live-props-updated-from-parent-value'] = $dehydratedProps;
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
        $this->attributes['data-live-events-to-emit-value'] = $events;
    }

    public function setBrowserEventsToDispatch(array $browserEventsToDispatch): void
    {
        $this->attributes['data-live-events-to-dispatch-value'] = $browserEventsToDispatch;
    }

    public function setRequestMethod(string $requestMethod): void
    {
        $this->attributes['data-live-request-method-value'] = $requestMethod;
    }

    public function setQueryUrlMapping(array $queryUrlMapping): void
    {
        $this->attributes['data-live-query-mapping-value'] = $queryUrlMapping;
    }

    private function escapeAttribute(string $value): string
    {
        if (class_exists(EscaperRuntime::class)) {
            return $this->twig->getRuntime(EscaperRuntime::class)->escape($value, 'html_attr');
        }

        if (method_exists(EscaperExtension::class, 'escape')) {
            return EscaperExtension::escape($this->twig, $value, 'html_attr');
        }

        // since twig/twig 3.9.0: Using the internal "twig_escape_filter" function is deprecated.
        return (string) twig_escape_filter($this->twig, $value, 'html_attr');
    }
}
