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
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Metadata\LiveComponentMetadataFactory;
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
        private RouterInterface $router,
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest()
            || !$event->getResponse()->isSuccessful()
            || !$request->attributes->has('_live_component')
            || !$request->attributes->has('_mounted_component')
            || !($previousLiveUrl = $request->headers->get(self::URL_HEADER))
        ) {
            return;
        }

        /** @var MountedComponent $mounted */
        $mounted = $request->attributes->get('_mounted_component');

        [$pathProps, $queryProps] = $this->extractUrlLiveProps($mounted);

        $event->getResponse()->headers->set(self::URL_HEADER, $this->generateNewLiveUrl($previousLiveUrl, $pathProps, $queryProps));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * @return array{ array<string, mixed>, array<string, mixed> }
     */
    private function extractUrlLiveProps(MountedComponent $mounted): array
    {
        $pathProps = $queryProps = [];

        $mountedMetadata = $this->metadataFactory->getMetadata($mounted->getName());

        if ([] !== $urlMappings = $mountedMetadata->getAllUrlMappings($mounted->getComponent())) {
            $dehydratedProps = $this->liveComponentHydrator->dehydrate($mounted->getComponent(), $mounted->getAttributes(), $mountedMetadata);
            $props = $dehydratedProps->getProps();

            foreach ($urlMappings as $name => $urlMapping) {
                if (\array_key_exists($name, $props)) {
                    if ($urlMapping->mapPath) {
                        $pathProps[$urlMapping->as ?? $name] = $props[$name];
                    } else {
                        $queryProps[$urlMapping->as ?? $name] = $props[$name];
                    }
                }
            }
        }

        return [$pathProps, $queryProps];
    }

    private function generateNewLiveUrl(string $previousUrl, array $pathProps, array $queryProps): string
    {
        $previousUrlParsed = parse_url($previousUrl);
        $newUrl = $previousUrlParsed['path'];
        $newQueryString = $previousUrlParsed['query'] ?? '';

        if ([] !== $pathProps) {
            $context = $this->router->getContext();
            try {
                // Re-create a context for the URL rendering the current LiveComponent
                $tmpContext = clone $context;
                $tmpContext->setMethod('GET');
                $this->router->setContext($tmpContext);

                $routeMatched = $this->router->match($previousUrlParsed['path']);
                $routeParams = [];
                foreach ($routeMatched as $k => $v) {
                    if ('_route' === $k || '_controller' === $k) {
                        continue;
                    }
                    $routeParams[$k] = \array_key_exists($k, $pathProps) ? $pathProps[$k] : $v;
                }

                $newUrl = $this->router->generate($routeMatched['_route'], $routeParams);
            } catch (ResourceNotFoundException) {
                // reuse the previous URL path
            } finally {
                $this->router->setContext($context);
            }
        }

        if ([] !== $queryProps) {
            $previousQueryString = [];

            if (isset($previousUrlParsed['query'])) {
                parse_str($previousUrlParsed['query'], $previousQueryString);
            }

            $newQueryString = http_build_query([...$previousQueryString, ...$queryProps]);
        }

        return $newUrl.($newQueryString ? '?'.$newQueryString : '');
    }
}
