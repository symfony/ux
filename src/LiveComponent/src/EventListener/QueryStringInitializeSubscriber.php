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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
use Symfony\UX\LiveComponent\Util\QueryStringPropsExtractor;
use Symfony\UX\TwigComponent\Event\PreMountEvent;

/**
 * @author Nicolas Rigaud <squrious@protonmail.com>
 *
 * @internal
 */
class QueryStringInitializeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly LiveComponentMetadataFactory $metadataFactory,
        private readonly QueryStringPropsExtractor $queryStringPropsExtractor,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreMountEvent::class => 'onPreMount',
        ];
    }

    public function onPreMount(PreMountEvent $event): void
    {
        if (!$event->getMetadata()->get('live', false)) {
            // Not a live component
            return;
        }

        $request = $this->requestStack->getMainRequest();

        if (null === $request) {
            return;
        }

        $metadata = $this->metadataFactory->getMetadata($event->getMetadata()->getName());

        if (!$metadata->hasQueryStringBindings()) {
            return;
        }

        $queryStringData = $this->queryStringPropsExtractor->extract($request, $metadata, $event->getComponent());

        $event->setData(array_merge($event->getData(), $queryStringData));
    }
}
