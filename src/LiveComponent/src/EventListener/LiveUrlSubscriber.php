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
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Util\UrlFactory;
use Symfony\UX\TwigComponent\MountedComponent;

/**
 * @internal
 */
class LiveUrlSubscriber implements EventSubscriberInterface
{
    private const URL_HEADER = 'X-Live-Url';

    public function __construct(
        private LiveComponentMetadataFactory $metadataFactory,
        private LiveComponentHydrator $liveComponentHydrator,
        private UrlFactory $urlFactory,
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->attributes->has('_live_component')) {
            return;
        }

        if (!$request->attributes->has('_mounted_component')) {
            return;
        }

        $newLiveUrl = null;
        if ($previousLiveUrl = $request->headers->get(self::URL_HEADER)) {
            $mounted = $request->attributes->get('_mounted_component');
            $liveProps = $this->getLiveProps($mounted);
            $newLiveUrl = $this->urlFactory->createFromPreviousAndProps($previousLiveUrl, $liveProps['path'], $liveProps['query']);
        }

        if ($newLiveUrl) {
            $event->getResponse()->headers->set(self::URL_HEADER, $newLiveUrl);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * @return array{
     *     path: array<string, mixed>,
     *     query: array<string, mixed>
     * }
     */
    private function getLiveProps(MountedComponent $mounted): array
    {
        $metadata = $this->metadataFactory->getMetadata($mounted->getName());

        $dehydratedProps = $this->liveComponentHydrator->dehydrate(
            $mounted->getComponent(),
            $mounted->getAttributes(),
            $metadata
        );

        $values = $dehydratedProps->getProps();

        $urlLiveProps = [
            'path' => [],
            'query' => [],
        ];

        foreach ($metadata->getAllUrlMappings($mounted->getComponent()) as $name => $urlMapping) {
            if (isset($values[$name]) && $urlMapping) {
                $urlLiveProps[$urlMapping->mapPath ? 'path' : 'query'][$urlMapping->as ?? $name] = $values[$name];
            }
        }

        return $urlLiveProps;
    }
}
