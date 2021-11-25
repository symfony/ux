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
use Symfony\Component\HttpFoundation\JsonResponse;
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
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentRenderer;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Ryan Weaver <ryan@symfonycasts.com>
 *
 * @experimental
 */
class LiveComponentSubscriber implements EventSubscriberInterface, ServiceSubscriberInterface
{
    private const JSON_FORMAT = 'live-component-json';
    private const JSON_CONTENT_TYPE = 'application/vnd.live-component+json';

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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

        $request->setFormat(self::JSON_FORMAT, self::JSON_CONTENT_TYPE);

        // the default "action" is get, which does nothing
        $action = $request->get('action', 'get');
        $componentName = (string) $request->get('component');

        try {
            $config = $this->container->get(ComponentFactory::class)->configFor($componentName);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException(sprintf('Component "%s" not found.', $componentName), $e);
        }

        $request->attributes->set('_component_template', $config['template']);

        if ('get' === $action) {
            $defaultAction = trim($config['default_action'] ?? '__invoke', '()');
            $componentClass = $config['class'];

            if (!method_exists($componentClass, $defaultAction)) {
                // todo should this check be in a compiler pass to ensure fails at compile time?
                throw new \LogicException(sprintf('Live component "%s" requires the default action method "%s".%s', $componentClass, $defaultAction, '__invoke' === $defaultAction ? ' Either add this method or use the DefaultActionTrait' : ''));
            }

            // set default controller for "default" action
            $request->attributes->set('_controller', sprintf('%s::%s', $config['service_id'], $defaultAction));
            $request->attributes->set('_component_default_action', true);

            return;
        }

        if (!$request->isMethod('post')) {
            throw new MethodNotAllowedHttpException(['POST']);
        }

        if (
            $this->container->has(CsrfTokenManagerInterface::class) &&
            !$this->container->get(CsrfTokenManagerInterface::class)->isTokenValid(new CsrfToken($componentName, $request->headers->get('X-CSRF-TOKEN')))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        $request->attributes->set('_controller', sprintf('%s::%s', $config['service_id'], $action));
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isLiveComponentRequest($request)) {
            return;
        }

        $data = array_merge(
            $request->query->all(),
            $request->request->all()
        );

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

        $this->container->get(LiveComponentHydrator::class)->hydrate($component, $data);

        // extra variables to be made available to the controller
        // (for "actions" only)
        parse_str($request->query->get('values'), $values);
        $request->attributes->add($values);
        $request->attributes->set('_component', $component);
    }

    public function onKernelView(ViewEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->isLiveComponentRequest($request)) {
            return;
        }

        $response = $this->createResponse($request->attributes->get('_component'), $request);

        $event->setResponse($response);
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->isLiveComponentRequest($request)) {
            return;
        }

        if (!$event->getThrowable() instanceof UnprocessableEntityHttpException) {
            return;
        }

        $component = $request->attributes->get('_component');

        // in case the exception was too early somehow
        if (!$component) {
            return;
        }

        $response = $this->createResponse($component, $request);
        $event->setResponse($response);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (!$this->isLiveComponentRequest($request)) {
            return;
        }

        if (!$this->isLiveComponentJsonRequest($request)) {
            return;
        }

        if (!$response->isRedirection()) {
            return;
        }

        $event->setResponse(new JsonResponse([
            'redirect_url' => $response->headers->get('Location'),
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

    private function createResponse(object $component, Request $request): Response
    {
        foreach (AsLiveComponent::beforeReRenderMethods($component) as $method) {
            $component->{$method->name}();
        }

        $html = $this->container->get(ComponentRenderer::class)->render(
            $component,
            $request->attributes->get('_component_template')
        );

        if ($this->isLiveComponentJsonRequest($request)) {
            return new JsonResponse(
                [
                    'html' => $html,
                    'data' => $this->container->get(LiveComponentHydrator::class)->dehydrate($component),
                ],
                200,
                ['Content-Type' => self::JSON_CONTENT_TYPE]
            );
        }

        return new Response($html);
    }

    private function isLiveComponentRequest(Request $request): bool
    {
        return 'live_component' === $request->attributes->get('_route');
    }

    private function isLiveComponentJsonRequest(Request $request): bool
    {
        return \in_array($request->getPreferredFormat(), [self::JSON_FORMAT, 'json'], true);
    }
}
