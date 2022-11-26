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
use Symfony\Component\HttpFoundation\Exception\JsonException;
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
    private const REDIRECT_HEADER = 'X-Live-Redirect';

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

        if ($request->attributes->has('_controller')) {
            return;
        }

        // the default "action" is get, which does nothing
        $action = $request->attributes->get('action', 'get');
        $componentName = (string) $request->attributes->get('component');

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

        if ('_batch' === $action) {
            // use batch controller
            $data = $this->parseDataFor($request);

            $request->attributes->set('_controller', 'ux.live_component.batch_action_controller');
            $request->attributes->set('serviceId', $metadata->getServiceId());
            $request->attributes->set('actions', $data['actions']);
            $request->attributes->set('_mounted_component', $this->hydrateComponent(
                $this->container->get(ComponentFactory::class)->get($componentName),
                $componentName,
                $request
            ));
            $request->attributes->set('_is_live_batch_action', true);

            return;
        }

        $request->attributes->set('_controller', sprintf('%s::%s', $metadata->getServiceId(), $action));
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isLiveComponentRequest($request)) {
            return;
        }

        if ($request->attributes->get('_is_live_batch_action')) {
            return;
        }

        $controller = $event->getController();

        if (!\is_array($controller) || 2 !== \count($controller)) {
            throw new \RuntimeException('Not a valid live component.');
        }

        [$component, $action] = $controller;

        if (!\is_object($component)) {
            throw new \RuntimeException('Not a valid live component.');
        }

        if (!$request->attributes->get('_component_default_action', false) && !AsLiveComponent::isActionAllowed($component, $action)) {
            throw new NotFoundHttpException(sprintf('The action "%s" either doesn\'t exist or is not allowed in "%s". Make sure it exist and has the LiveAction attribute above it.', $action, \get_class($component)));
        }

        /*
         * Either we:
         *      A) We do NOT have a _mounted_component, so hydrate $component
         *          (normal situation, rendering a single component)
         *      B) We DO have a _mounted_component, so no need to hydrate,
         *          but we DO need to make sure it's set as the controller.
         *          (sub-request during batch controller)
         */
        if (!$request->attributes->has('_mounted_component')) {
            $request->attributes->set('_mounted_component', $this->hydrateComponent(
                $component,
                $request->attributes->get('_component_name'),
                $request
            ));
        } else {
            // override the component with our already-mounted version
            $component = $request->attributes->get('_mounted_component')->getComponent();
            $event->setController([
                $component,
                $action,
            ]);
        }

        // read the action arguments from the request, unless they're already set (batch sub-requests)
        $actionArguments = $request->attributes->get('_component_action_args', $this->parseDataFor($request)['args']);
        // extra variables to be made available to the controller
        // (for "actions" only)
        foreach (LiveArg::liveArgs($component, $action) as $parameter => $arg) {
            if (isset($actionArguments[$arg])) {
                $request->attributes->set($parameter, $actionArguments[$arg]);
            }
        }
    }

    /**
     * @return array{
     *     data: array,
     *     args: array,
     *     actions: array
     *     childrenFingerprints: array
     * }
     */
    private static function parseDataFor(Request $request): array
    {
        if (!$request->attributes->has('_live_request_data')) {
            if ($request->query->has('data')) {
                $liveRequestData = [
                    'data' => self::parseJsonFromQuery($request, 'data'),
                    'args' => [],
                    'actions' => [],
                    'childrenFingerprints' => self::parseJsonFromQuery($request, 'childrenFingerprints'),
                ];
            } else {
                $requestData = $request->toArray();

                $liveRequestData = [
                    'data' => $requestData['data'] ?? [],
                    'args' => $requestData['args'] ?? [],
                    'actions' => $requestData['actions'] ?? [],
                    'childrenFingerprints' => $requestData['childrenFingerprints'] ?? [],
                ];
            }

            $request->attributes->set('_live_request_data', $liveRequestData);
        }

        return $request->attributes->get('_live_request_data');
    }

    public function onKernelView(ViewEvent $event): void
    {
        if (!$this->isLiveComponentRequest($request = $event->getRequest())) {
            return;
        }

        if (!$event->isMainRequest()) {
            // sub-request, so skip rendering
            $event->setResponse(new Response());

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
            self::REDIRECT_HEADER => 1,
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

        return new Response($this->container->get(ComponentRenderer::class)->render($mounted), 200, [
            'Content-Type' => self::HTML_CONTENT_TYPE,
        ]);
    }

    private function isLiveComponentRequest(Request $request): bool
    {
        return str_starts_with($request->attributes->get('_route'), 'ux_live_component');
    }

    private function hydrateComponent(object $component, string $componentName, Request $request): MountedComponent
    {
        $hydrator = $this->container->get(LiveComponentHydrator::class);
        \assert($hydrator instanceof LiveComponentHydrator);

        $mountedComponent = $hydrator->hydrate(
            $component,
            $this->parseDataFor($request)['data'],
            $componentName
        );

        $mountedComponent->addExtraMetadata(
            InterceptChildComponentRenderSubscriber::CHILDREN_FINGERPRINTS_METADATA_KEY,
            $this->parseDataFor($request)['childrenFingerprints']
        );

        return $mountedComponent;
    }

    private static function parseJsonFromQuery(Request $request, string $key): array
    {
        if (!$request->query->has($key)) {
            return [];
        }

        try {
            return json_decode($request->query->get($key), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new JsonException(sprintf('Invalid JSON on query string %s.', $key), 0, $exception);
        }
    }
}
