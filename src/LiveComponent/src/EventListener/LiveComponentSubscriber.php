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

use Doctrine\Common\Annotations\Reader;
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
use Symfony\UX\LiveComponent\Attribute\BeforeReRender;
use Symfony\UX\TwigComponent\ComponentFactory;
use Symfony\UX\TwigComponent\ComponentRenderer;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\DefaultComponentController;
use Symfony\UX\LiveComponent\LiveComponentInterface;

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

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public static function getSubscribedServices(): array
    {
        return [
            ComponentFactory::class,
            ComponentRenderer::class,
            LiveComponentHydrator::class,
            Reader::class,
            '?'.CsrfTokenManagerInterface::class,
        ];
    }

    public function onKernelRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->isLiveComponentRequest($request)) {
            return;
        }

        $request->setFormat(self::JSON_FORMAT, self::JSON_CONTENT_TYPE);

        // the default "action" is get, which does nothing
        $action = $request->get('action', 'get');
        $componentName = (string) $request->get('component');

        if ('get' === $action) {
            // set default controller for "default" action
            $request->attributes->set(
                '_controller',
                new DefaultComponentController($this->container->get(ComponentFactory::class)->get($componentName))
            );

            return;
        }

        if (!$request->isMethod('post')) {
            throw new MethodNotAllowedHttpException(['POST']);
        }

        if (
            $this->container->has(CsrfTokenManagerInterface::class) &&
            !$this->container->get(CsrfTokenManagerInterface::class)->isTokenValid(new CsrfToken($componentName, $request->headers->get('X-CSRF-TOKEN'))))
        {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        try {
            $componentServiceId = $this->container->get(ComponentFactory::class)->serviceIdFor($componentName);
        } catch (\InvalidArgumentException $e) {
            throw new NotFoundHttpException('Component not found.');
        }

        $request->attributes->set('_controller', sprintf('%s::%s', $componentServiceId, $action));
    }

    public function onKernelController(ControllerEvent $event)
    {
        $request = $event->getRequest();

        if (!$this->isLiveComponentRequest($request)) {
            return;
        }

        // TODO: also allow reading from $request->attributes in case
        // some data is part of the URL string - see #42
        $data = array_merge(
            $request->query->all(),
            $request->request->all()
        );

        $component = $event->getController();
        $action = null;

        if (\is_array($component)) {
            // action is being called
            $action = $component[1];
            $component = $component[0];
        }

        if ($component instanceof DefaultComponentController) {
            $component = $component->getComponent();
        }

        if (!$component instanceof LiveComponentInterface) {
            throw new NotFoundHttpException('this is not a live component!');
        }

        if (null !== $action && !$this->container->get(LiveComponentHydrator::class)->isActionAllowed($component, $action)) {
            throw new NotFoundHttpException('invalid action');
        }

        if (!\is_array($data)) {
            throw new NotFoundHttpException('invalid component data');
        }

        $this->container->get(LiveComponentHydrator::class)->hydrate($component, $data);

        // extra variables to be made available to the controller
        // (for "actions" only)
        parse_str($request->query->get('values'), $values);
        $request->attributes->add($values);
        $request->attributes->set('_component', $component);
    }

    public function onKernelView(ViewEvent $event)
    {
        $request = $event->getRequest();
        if (!$this->isLiveComponentRequest($request)) {
            return;
        }

        /** @var LiveComponentInterface $component */
        $component = $request->attributes->get('_component');

        if (!$component instanceof LiveComponentInterface) {
            throw new \InvalidArgumentException('Somehow we are missing the _component attribute');
        }

        $response = $this->createResponse($component, $request);

        $event->setResponse($response);
    }

    public function onKernelException(ExceptionEvent $event)
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

    public static function getSubscribedEvents()
    {
        return [
            RequestEvent::class => 'onKernelRequest',
            ControllerEvent::class => 'onKernelController',
            ViewEvent::class => 'onKernelView',
            ResponseEvent::class => 'onKernelResponse',
            ExceptionEvent::class => 'onKernelException',
        ];
    }

    private function createResponse(LiveComponentInterface $component, Request $request): Response
    {
        foreach ($this->beforeReRenderMethods($component) as $method) {
            $component->{$method->name}();
        }

        $html = $this->container->get(ComponentRenderer::class)->render($component);

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
        return $request->attributes->get('_route') === 'live_component';
    }

    private function isLiveComponentJsonRequest(Request $request): bool
    {
        return \in_array($request->getPreferredFormat(), [self::JSON_FORMAT, 'json'], true);
    }

    /**
     * @return \ReflectionMethod[]
     */
    private function beforeReRenderMethods(LiveComponentInterface $component): iterable
    {
        foreach ((new \ReflectionClass($component))->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($this->container->get(Reader::class)->getMethodAnnotation($method, BeforeReRender::class)) {
                yield $method;
            }
        }
    }
}
