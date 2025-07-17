<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\UX\TwigComponent\Event\PostMountEvent;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;

/**
 * Handles the "defer" key, which causes the component to be rendered asynchronously.
 *
 * @internal
 */
final class DeferLiveComponentSubscriber implements EventSubscriberInterface
{
    private const DEFAULT_LOADING_TAG = 'div';

    private const DEFAULT_LOADING_TEMPLATE = null;

    public function onPostMount(PostMountEvent $event): void
    {
        $data = $event->getData();
        if (!$event->getMetadata()->get('live', false)) {
            // Not a live component
            return;
        }

        if (\array_key_exists('defer', $data)) {
            trigger_deprecation('symfony/ux-live-component', '2.17', 'The "defer" attribute is deprecated and will be removed in 3.0. Use the "loading" attribute instead set to the value "defer".');
            if ($data['defer']) {
                $event->addExtraMetadata('loading', 'defer');
            }
            unset($data['defer']);
        }

        if (\array_key_exists('loading', $data)) {
            // Ignored values: false / null / ''
            if ($loading = $data['loading']) {
                if (!\is_scalar($loading)) {
                    throw new \InvalidArgumentException(\sprintf('The "loading" attribute value must be scalar, "%s" passed.', get_debug_type($loading)));
                }
                if (!\in_array($loading, ['defer', 'lazy'], true)) {
                    throw new \InvalidArgumentException(\sprintf('Invalid "loading" attribute value "%s". Accepted values: "defer" and "lazy".', $loading));
                }
                $event->addExtraMetadata('loading', $loading);
            }
            unset($data['loading']);
        }

        if (\array_key_exists('loading-template', $data)) {
            $event->addExtraMetadata('loading-template', $data['loading-template']);
            unset($data['loading-template']);
        }

        if (\array_key_exists('loading-tag', $data)) {
            $event->addExtraMetadata('loading-tag', $data['loading-tag']);
            unset($data['loading-tag']);
        }

        $event->setData($data);
    }

    public function onPreRender(PreRenderEvent $event): void
    {
        $mountedComponent = $event->getMountedComponent();

        if (!$mountedComponent->hasExtraMetadata('loading')) {
            return;
        }
        if (!\in_array($mountedComponent->getExtraMetadata('loading'), ['defer', 'lazy'], true)) {
            return;
        }

        $componentTemplate = $event->getMetadata()->getTemplate();
        $event->setTemplate('@LiveComponent/deferred.html.twig');

        $variables = $event->getVariables();
        $variables['loadingTemplate'] = self::DEFAULT_LOADING_TEMPLATE;
        $variables['loadingTag'] = self::DEFAULT_LOADING_TAG;
        $variables['loading'] = $mountedComponent->getExtraMetadata('loading');

        if ($mountedComponent->hasExtraMetadata('loading-template')) {
            $variables['loadingTemplate'] = $mountedComponent->getExtraMetadata('loading-template');
        }

        if ($mountedComponent->hasExtraMetadata('loading-tag')) {
            $variables['loadingTag'] = $mountedComponent->getExtraMetadata('loading-tag');
        }

        $variables['componentTemplate'] = $componentTemplate;

        $event->setVariables($variables);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostMountEvent::class => ['onPostMount'],
            PreRenderEvent::class => ['onPreRender'],
        ];
    }
}
