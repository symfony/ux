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

use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentMetadata;
use Symfony\UX\TwigComponent\ComponentRenderer;
use Symfony\UX\TwigComponent\MountedComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 *
 * @internal
 */
class LiveComponentSubscriber implements EventSubscriberInterface, ServiceSubscriberInterface
{
    private const HTML_CONTENT_TYPE = 'application/vnd.live-component+html';

    public function __construct(private ContainerInterface $container)
    {
    }

    public static function getSubscribedServices(): array
    {
        return [
            ComponentRenderer::class,
            ComponentFactory::class,
            LiveComponentHydrator::class,
            '?'.CsrfTokenManagerInterface::class,
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isLiveComponentRequest($request)) {
            return;
        }

        // the default "action" is get, which does nothing
        $action = $request->get('action', 'get');
        $componentName = (string) $request->get('component');

        $request->attributes->set('_component_name', $componentName);

        try {
            /** @var ComponentMetadata $metadata */
            $metadata = $this->container->get(ComponentFactory::class)->metadataFor($componentName);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException(sprintf('Component "%s" not found.', $componentName), $e);
        }

        if (!$metadata->get('live', false)) {
            throw new NotFoundHttpException(sprintf('"%s" (%s) is not a Live Component.', $metadata->getClass(), $componentName));
        }

        if ('get' === $action) {
            $defaultAction = trim($metadata->get('default_action', '__invoke'), '()');

            // set default controller for "default" action
            $request->attributes->set('_controller', sprintf('%s::%s', $metadata->getServiceId(), $defaultAction));
            $request->attributes->set('_component_default_action', true);

            return;
        }

        if (!$request->isMethod('post')) {
            throw new MethodNotAllowedHttpException(['POST']);
        }

        if (
            $this->container->has(CsrfTokenManagerInterface::class) &&
            $metadata->get('csrf') &&
            !$this->container->get(CsrfTokenManagerInterface::class)->isTokenValid(new CsrfToken($componentName, $request->headers->get('X-CSRF-TOKEN')))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $request->attributes->set('_controller', sprintf('%s::%s', $metadata->getServiceId(), $action));
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isLiveComponentRequest($request)) {
            return;
        }

        if ($request->query->has('data')) {
            // ?data=
            $data = json_decode($request->query->get('data'), true, 512, \JSON_THROW_ON_ERROR);
        } else {
            // OR body of the request is JSON
            $data = json_decode($request->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        }

        if (!\is_array($controller = $event->getController()) || 2 !== \count($controller)) {
            throw new \RuntimeException('Not a valid live component.');
        }

        [$component, $action] = $controller;

        if (!\is_object($component)) {
            throw new \RuntimeException('Not a valid live component.');
        }

        if (!$request->attributes->get('_component_default_action', false) && !AsLiveComponent::isActionAllowed($component, $action)) {
            throw new NotFoundHttpException(sprintf('The action "%s" either doesn\'t exist or is not allowed in "%s". Make sure it exist and has the LiveAction attribute above it.', $action, \get_class($component)));
        }

        $mounted = $this->container->get(LiveComponentHydrator::class)->hydrate(
            $component,
            $data,
            $request->attributes->get('_component_name')
        );

        $request->attributes->set('_mounted_component', $mounted);

        if (!\is_string($queryString = $request->query->get('args'))) {
            return;
        }

        // extra variables to be made available to the controller
        // (for "actions" only)
        parse_str($queryString, $args);

        foreach (LiveArg::liveArgs($component, $action) as $parameter => $arg) {
            if (isset($args[$arg])) {
                $request->attributes->set($parameter, $args[$arg]);
            }
        }
    }

    public function onKernelView(ViewEvent $event): void
    {
        if (!$this->isLiveComponentRequest($request = $event->getRequest())) {
            return;
        }

        $event->setResponse($this->createResponse($request->attributes->get('_mounted_component')));
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$this->isLiveComponentRequest($request = $event->getRequest())) {
            return;
        }

        if (!$event->getThrowable() instanceof UnprocessableEntityHttpException) {
            return;
        }

        // in case the exception was too early somehow
        if (!$mounted = $request->attributes->get('_mounted_component')) {
            return;
        }

        $event->setResponse($this->createResponse($mounted));
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$this->isLiveComponentRequest($request)) {
            return;
        }

        if (!\in_array(self::HTML_CONTENT_TYPE, $request->getAcceptableContentTypes(), true)) {
            return;
        }

        if (!$response->isRedirection()) {
            return;
        }

        $event->setResponse(new Response(null, 204, [
            'Location' => $response->headers->get('Location'),
            'Content-Type' => self::HTML_CONTENT_TYPE,
        ]));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onKernelRequest',
            ControllerEvent::class => 'onKernelController',
            ViewEvent::class => 'onKernelView',
            ResponseEvent::class => 'onKernelResponse',
            ExceptionEvent::class => 'onKernelException',
        ];
    }

    private function createResponse(MountedComponent $mounted): Response
    {
        $component = $mounted->getComponent();

        foreach (AsLiveComponent::preReRenderMethods($component) as $method) {
            $component->{$method->name}();
        }

        return new Response($this->container->get(ComponentRenderer::class)->render($mounted));
    }

    private function isLiveComponentRequest(Request $request): bool
    {
        return 'live_component' === $request->attributes->get('_route');
    }
}
