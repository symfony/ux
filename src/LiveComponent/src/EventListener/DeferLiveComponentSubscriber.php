<?php

declare(strict_types=1);

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
        if (\array_key_exists('defer', $data)) {
            $event->addExtraMetadata('defer', true);
            unset($data['defer']);
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

        if (!$mountedComponent->hasExtraMetadata('defer')) {
            return;
        }

        $componentTemplate = $event->getMetadata()->getTemplate();
        $event->setTemplate('@LiveComponent/deferred.html.twig');

        $variables = $event->getVariables();
        $variables['loadingTemplate'] = self::DEFAULT_LOADING_TEMPLATE;
        $variables['loadingTag'] = self::DEFAULT_LOADING_TAG;

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
