<?php

declare(strict_types=1);

namespace Symfony\UX\LiveComponent\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\UX\TwigComponent\Event\PostMountEvent;
use Symfony\UX\TwigComponent\Event\PreRenderEvent;

final class DeferLiveComponentSubscriber implements EventSubscriberInterface
{
    private const DEFAULT_LOADING_TAG = 'div';

    private const DEFAULT_LOADING_TEMPLATE = null;

    public function onPostMount(PostMountEvent $event): void
    {
        $data = $event->getData();
        if (\array_key_exists('defer', $data)) {
            $event->addExtraMetadata('defer', true);
            unset($event->getData()['defer']);
        }

        if (\array_key_exists('loading-template', $data)) {
            $event->addExtraMetadata('loading-template', $data['loading-template']);
            unset($event->getData()['loading-template']);
        }

        if (\array_key_exists('loading-tag', $data)) {
            $event->addExtraMetadata('loading-tag', $data['loading-tag']);
            unset($event->getData()['loading-tag']);
        }

        $event->setData($data);
    }

    public function onPreRender(PreRenderEvent $event): void
    {
        $mountedComponent = $event->getMountedComponent();

        if (!$mountedComponent->hasExtraMetadata('defer')) {
            return;
        }

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
